<?php

namespace App\Command;

use App\Service\GoalAchievementService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-reading-goals',
    description: 'Update reading goals and check for achievements',
)]
class UpdateReadingGoalsCommand extends Command
{
    public function __construct(
        private GoalAchievementService $goalAchievementService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Update goals for a specific user ID')
            ->setDescription('Update reading goals and check for achievements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getOption('user');

        if ($userId) {
            $io->title('Updating Reading Goals for Specific User');
            $io->text("Updating goals for user ID: $userId");
            // TODO: Implement single user update
            $io->warning('Single user update not yet implemented');
        } else {
            $io->title('Updating Reading Goals for All Users');
            $io->text('Starting periodic goal updates...');

            $startTime = microtime(true);
            $this->goalAchievementService->performPeriodicGoalUpdates();
            $endTime = microtime(true);

            $duration = round($endTime - $startTime, 2);
            $io->success("Goal updates completed in {$duration} seconds");
        }

        return Command::SUCCESS;
    }
}