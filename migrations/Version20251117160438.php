<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117160438 extends AbstractMigration
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
            $this->addSql('ALTER TABLE reading_progress ADD COLUMN IF NOT EXISTS current_page INT DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at SET DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at SET DEFAULT NULL');
            // Only set NOT NULL if column is currently nullable
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'user\' AND column_name = \'is_verified\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE "user" ALTER COLUMN is_verified SET NOT NULL;
                END IF;
            END $$;');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE reading_progress ADD current_page INT DEFAULT NULL');
            $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_verified is_verified TINYINT(1) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE reading_progress DROP COLUMN IF EXISTS current_page');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN is_verified DROP DEFAULT');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at DROP DEFAULT');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at DROP DEFAULT');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE reading_progress DROP current_page');
            $this->addSql('ALTER TABLE user CHANGE is_verified is_verified TINYINT(1) DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        }
    }
}
