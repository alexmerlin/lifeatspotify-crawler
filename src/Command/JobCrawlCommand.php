<?php

namespace App\Command;

use App\Provider\SpotifyProvider;
use App\Service\SpotifyService;
use App\Service\Storage\JsonStorageService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_filter;
use function array_map;
use function count;
use function getcwd;
use function implode;
use function sprintf;

/**
 * Class JobCrawlCommand
 * @package App\Command
 */
class JobCrawlCommand extends Command
{
    const ANSWER_YES = 'Yes';
    const ANSWER_NO = 'No';
    const ANSWERS = [
        self::ANSWER_NO,
        self::ANSWER_YES
    ];

    protected static $defaultName = 'job:crawl';
    protected static $defaultDescription = 'Crawl jobs';

    private SpotifyService $spotifyService;

    private JsonStorageService $jsonStorageService;

    /**
     * JobCrawlCommand constructor.
     * @param SpotifyService $spotifyService
     * @param JsonStorageService $jsonStorageService
     */
    public function __construct(SpotifyService $spotifyService, JsonStorageService $jsonStorageService)
    {
        parent::__construct(self::$defaultName);
        $this->spotifyService = $spotifyService;
        $this->jsonStorageService = $jsonStorageService;
    }

    /**
     * Configure command.
     */
    protected function configure(): void
    {
        $this
            ->addOption('location', 'l', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Location filter')
            ->addOption('category', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Category filter')
            ->addOption('job-type', 'j', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Job type filter')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * Read specified options and make sure they are not empty
         */
        $locations = array_filter(array_map('trim', $input->getOption('location')), 'strlen');
        $categories = array_filter(array_map('trim', $input->getOption('category')), 'strlen');
        $jobTypes = array_filter(array_map('trim', $input->getOption('job-type')), 'strlen');

        if (empty($locations) && empty($categories) && empty($jobTypes)) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'You did not specify any filter. Continue?',
                self::ANSWERS,
                self::ANSWER_NO
            );
            $question->setErrorMessage('%s is not a valid answer.');
            $answer = $helper->ask($input, $output, $question);
            if ($answer === self::ANSWER_NO) {
                return Command::SUCCESS;
            }
        }

        $provider = new SpotifyProvider();
        $provider->filterByLocation($locations)->filterByCategory($categories)->filterByJobType($jobTypes);
        $jobs = $this->spotifyService->getJobs($provider);
        $jobs = $this->spotifyService->readJobs($jobs);
        $this->jsonStorageService->dump(getcwd() . '/data/jobs.json', $jobs);

        $table = new Table($output);
        $table
            ->setHeaders(['Title', 'Location', 'Category', 'Job type', 'Experience', 'Professional'])
            ->setRows(
                array_map(function ($job) {
                    return [
                        $job['text'],
                        implode(' or ', $job['locations']),
                        implode(' -> ', $job['categories']),
                        $job['job_type']['name'],
                        $job['experience'] ?? 'not specified',
                        $job['professional'] ? 'yes' : 'no'
                    ];
                }, $jobs)
            );
        $table->render();

        $message = sprintf(
            '%d job(s) in %s in %s in %s',
            count($jobs),
            count($locations) > 0 ? implode(', ', $locations) : 'all locations',
            count($categories) > 0 ? implode(', ', $categories) : 'all categories',
            count($jobTypes) > 0 ? implode(', ', $jobTypes) : 'all job types',
        );
        $io = new SymfonyStyle($input, $output);
        count($jobs) > 0 ? $io->success($message) : $io->warning($message);

        return Command::SUCCESS;
    }
}
