<?php

namespace App\Command;

use App\Service\Storage\JsonStorageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_key_exists;
use function getcwd;
use function implode;

/**
 * Class JobViewCommand
 * @package App\Command
 */
class JobViewCommand extends Command
{
    protected static $defaultName = 'job:view';
    protected static $defaultDescription = 'View job from local file.';

    private JsonStorageService $jsonStorageService;

    /**
     * JobViewCommand constructor.
     * @param JsonStorageService $jsonStorageService
     */
    public function __construct(JsonStorageService $jsonStorageService)
    {
        parent::__construct(self::$defaultName);
        $this->jsonStorageService = $jsonStorageService;
    }

    /**
     * Configure command.
     */
    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Job ID');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getArgument('id');
        $jobs = $this->jsonStorageService->load(getcwd() . '/data/jobs.json');
        if (!array_key_exists($id, $jobs)) {
            $io->error('Could not find job identified by id: ' . $id);
            return Command::SUCCESS;
        }

        $job = $jobs[$id];
        $io->success($job['url']);
        $io->writeln(sprintf('                id: %s', $job['id']));
        $io->writeln(sprintf('             title: %s', $job['text']));
        $io->writeln(sprintf('          location: %s', implode(' or ', $job['locations'])));
        $io->writeln(sprintf('        categories: %s', implode(' -> ', $job['categories'])));
        $io->writeln(sprintf('          job type: %s', $job['job_type']['name']));
        $io->writeln(sprintf('        experience: %s', $job['experience'] ?? 'not specified'));
        $io->writeln(sprintf('      professional: %s', $job['professional'] ? 'yes' : 'no'));
        $io->writeln(sprintf('          headline: %s', $job['headline']));
        $io->writeln(sprintf('       description: %s', $job['description']));

        return Command::SUCCESS;
    }
}
