<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203014424 extends AbstractMigration
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
            $this->addSql('ALTER TABLE reading_progress ADD bookmarks JSON DEFAULT NULL, ADD notes TEXT DEFAULT NULL');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE reading_progress ADD bookmarks JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD notes LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE reading_progress DROP bookmarks, DROP notes');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE reading_progress DROP bookmarks, DROP notes');
        }
    }
}
