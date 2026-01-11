<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205120542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax - no COMMENT clause
            $this->addSql('ALTER TABLE book_reservations ADD expected_available_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE book_reservations ADD expected_available_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE book_reservations DROP expected_available_date');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE book_reservations DROP expected_available_date');
        }
    }
}
