<?php

namespace App\Provider;

/**
 * Class SpotifyProvider
 * @package App\Provider
 */
class SpotifyProvider
{
    private array $filters = [];
    private string $jobUrl = 'https://www.lifeatspotify.com/jobs/';
    private string $searchUrl = 'https://api-dot-new-spotifyjobs-com.nw.r.appspot.com/wp-json/animal/v1/job/search?';

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addFilter(string $key, string $value): self
    {
        $this->filters[$key] = $value;
        return $this;
    }

    /**
     * @param string $id
     * @return string
     */
    public function buildJobUrl(string $id): string
    {
        return $this->jobUrl . $id;
    }

    /**
     * @return string
     */
    public function buildSearchUrl(): string
    {
        return $this->searchUrl . http_build_query($this->filters);
    }

    /**
     * @param array $categories
     * @return $this
     */
    public function filterByCategory(array $categories): self
    {
        return $this->addFilter('c', implode(',', $categories));
    }

    /**
     * @param array $jobTypes
     * @return $this
     */
    public function filterByJobType(array $jobTypes): self
    {
        return $this->addFilter('j', implode(',', $jobTypes));
    }

    /**
     * @param array $locations
     * @return $this
     */
    public function filterByLocation(array $locations): self
    {
        return $this->addFilter('l', implode(',', $locations));
    }
}
