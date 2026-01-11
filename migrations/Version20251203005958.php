<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203005958 extends AbstractMigration
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
            // PostgreSQL syntax - Use IF NOT EXISTS to avoid errors if column already exists
            $this->addSql('ALTER TABLE livre ADD COLUMN IF NOT EXISTS is_borrowable BOOLEAN DEFAULT true');
            // Set NOT NULL only if column was just added or is currently nullable
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'livre\' AND column_name = \'is_borrowable\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE livre ALTER COLUMN is_borrowable SET NOT NULL;
                END IF;
            END $$;');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE livre ADD is_borrowable TINYINT(1) DEFAULT 1 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE livre DROP is_borrowable');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE livre DROP is_borrowable');
        }
    }
}
