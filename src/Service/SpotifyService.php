<?php

namespace App\Service;

use App\Provider\SpotifyProvider;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

use function htmlspecialchars_decode;
use function json_decode;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Class SpotifyService
 * @package App\Service
 */
class SpotifyService
{
    /**
     * @param SpotifyProvider $provider
     * @return array
     * @throws GuzzleException
     */
    public function getJobs(SpotifyProvider $provider): array
    {
        $jobs = [];

        $client = new Client();
        $response = $client->get($provider->buildSearchUrl());
        $json = json_decode($response->getBody()->getContents(), true);
        $rawJobs = $json['result'] ?? [];
        foreach ($rawJobs as $rawJob) {
            $rawJob['url'] = $provider->buildJobUrl($rawJob['id']);
            $jobs[] = $rawJob;
        }

        return $jobs;
    }

    /**
     * @param array $job
     * @return array
     * @throws GuzzleException
     */
    public function readJob(array $job): array
    {
        $client = new Client();
        $response = $client->get($job['url']);
        $html = $response->getBody()->getContents();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $job['categories'] = [];
        if (!empty($job['main_category']['name'])) {
            $job['categories'][] = $job['main_category']['name'];
        }
        if (!empty($job['sub_category']['name'])) {
            $job['categories'][] = $job['sub_category']['name'];
        }

        $job['locations'] = [];
        if (!empty($job['location']['location'])) {
            $job['locations'][] = $job['location']['location'];
        }
        if (!empty($job['is_remote']) && !empty($job['remote_name']['name'])) {
            $job['locations'][] = $job['remote_name']['name'];
        }

        $job['text'] = htmlspecialchars_decode($job['text']);
        $job['headline'] =
            trim($xpath->query('/html/body/div/div/main/div/div[2]/div/div[2]/div')->item(0)->nodeValue);
        $job['description'] =
            trim($xpath->query('/html/body/div/div/main/div/div[2]/div/div[4]/div')->item(0)->nodeValue);

        /**
         * Detect years of experience
         */
        $job['experience'] = null;
        preg_match('/(\d+)\+(?: )?years (?:.* )?experience/i', $html, $matches);
        if (!empty($matches[1])) {
            $job['experience'] = sprintf('%s+ years', $matches[1]);
        }
        if (empty($job['experience'])) {
            preg_match('/(\d+\s\-\s\d+)(?: )?years (?:.* )?experience/i', $html, $matches);
            if (!empty($matches[1])) {
                $job['experience'] = sprintf('%s years', $matches[1]);
            }
        }

        /**
         * Detect if job is targeted for professionals
         */
        $job['professional'] = false;
        preg_match('/(senior)/i', $job['text'], $matches);
        if (!empty($matches[1])) {
            $job['professional'] = true;
        }

        return $job;
    }

    /**
     * @param array $incompleteJobs
     * @return array
     * @throws GuzzleException
     */
    public function readJobs(array $incompleteJobs): array
    {
        $completeJobs = [];

        foreach ($incompleteJobs as $incompleteJob) {
            $completeJobs[$incompleteJob['id']] = $this->readJob($incompleteJob);
        }

        return $completeJobs;
    }
}
