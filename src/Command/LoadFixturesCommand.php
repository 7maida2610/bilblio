<?php

namespace App\Command;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\BannerFixtures;
use App\DataFixtures\LoanFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Load data fixtures into the database',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge database before loading fixtures')
            ->setHelp('This command loads fixtures into the database. Use --purge to clear existing data first.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $purge = $input->getOption('purge');

        if ($purge) {
            $io->warning('This will delete all existing data!');
            if (!$io->confirm('Are you sure you want to continue?', false)) {
                $io->info('Command cancelled.');
                return Command::FAILURE;
            }

            $io->section('Purging database...');
            $this->purgeDatabase($io);
        }

        $io->title('Loading Fixtures');

        // Create ObjectManager wrapper for fixtures
        $objectManager = new class($this->entityManager) implements ObjectManager {
            public function __construct(private EntityManagerInterface $em) {}

            public function find($className, $id) { return $this->em->find($className, $id); }
            public function persist($object): void { $this->em->persist($object); }
            public function remove($object): void { $this->em->remove($object); }
            public function flush(): void { $this->em->flush(); }
            public function clear($objectName = null): void { $this->em->clear($objectName); }
            public function detach($object): void { $this->em->detach($object); }
            public function refresh($object): void { $this->em->refresh($object); }
            public function getRepository($className) { return $this->em->getRepository($className); }
            public function getClassMetadata($className) { return $this->em->getClassMetadata($className); }
            public function getMetadataFactory() { return $this->em->getMetadataFactory(); }
            public function initializeObject($obj): void {}
            public function contains($object): bool { return $this->em->contains($object); }
        };

        // Load fixtures
        $io->section('Loading AppFixtures...');
        $appFixtures = new AppFixtures($this->passwordHasher);
        $appFixtures->load($objectManager);
        $io->success('AppFixtures loaded');

        $io->section('Loading BannerFixtures...');
        $bannerFixtures = new BannerFixtures();
        $bannerFixtures->load($objectManager);
        $io->success('BannerFixtures loaded');

        $io->section('Loading LoanFixtures...');
        $loanFixtures = new LoanFixtures();
        $loanFixtures->load($objectManager);
        $io->success('LoanFixtures loaded');

        $io->newLine();
        $io->success('All fixtures loaded successfully!');

        return Command::SUCCESS;
    }

    private function purgeDatabase(SymfonyStyle $io): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        if ($platform === 'postgresql') {
            // PostgreSQL: Disable foreign key checks
            $connection->executeStatement('SET session_replication_role = replica;');
            foreach ($tables as $table) {
                $connection->executeStatement("TRUNCATE TABLE \"$table\" CASCADE;");
                $io->writeln("  - Truncated: $table");
            }
            $connection->executeStatement('SET session_replication_role = DEFAULT;');
        } else {
            // MySQL
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');
            foreach ($tables as $table) {
                $connection->executeStatement("TRUNCATE TABLE `$table`;");
                $io->writeln("  - Truncated: $table");
            }
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        $io->success('Database purged');
    }
}
