<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116090619 extends AbstractMigration
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
            // PostgreSQL syntax - ADD COLUMN IF NOT EXISTS
            $this->addSql('ALTER TABLE livre ADD COLUMN IF NOT EXISTS pdf VARCHAR(255) DEFAULT NULL');
        } else {
            // MySQL syntax - Check if pdf column already exists before adding it
            $this->addSql('SET @sql = (SELECT IF(
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "livre" AND COLUMN_NAME = "pdf") > 0,
                "SELECT \'Column pdf already exists\'",
                "ALTER TABLE livre ADD pdf VARCHAR(255) DEFAULT NULL"
            ));');
            $this->addSql('PREPARE stmt FROM @sql;');
            $this->addSql('EXECUTE stmt;');
            $this->addSql('DEALLOCATE PREPARE stmt;');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE livre DROP COLUMN IF EXISTS pdf');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE livre DROP pdf');
        }
    }
}
