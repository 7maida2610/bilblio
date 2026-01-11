<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117135210 extends AbstractMigration
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
            // PostgreSQL syntax - Use IF NOT EXISTS to avoid errors if columns already exist
            $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS reset_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
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
            $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(255) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_verified is_verified TINYINT(1) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE "user" DROP reset_token');
            $this->addSql('ALTER TABLE "user" DROP reset_token_expires_at');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN is_verified DROP DEFAULT');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at DROP DEFAULT');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at DROP DEFAULT');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE user DROP reset_token, DROP reset_token_expires_at, CHANGE is_verified is_verified TINYINT(1) DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        }
    }
}
