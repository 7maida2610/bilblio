<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117125301 extends AbstractMigration
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
            // PostgreSQL syntax - use "user" (quoted) because user is a reserved word
            // Use IF NOT EXISTS to avoid errors if columns already exist
            $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS verification_token VARCHAR(255) DEFAULT NULL');
            
            // Set default value for existing rows if column was just added
            $this->addSql('UPDATE "user" SET is_verified = false WHERE is_verified IS NULL');
            
            // Now make is_verified NOT NULL (only if it's currently nullable)
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'user\' AND column_name = \'is_verified\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE "user" ALTER COLUMN is_verified SET NOT NULL;
                END IF;
            END $$;');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL, ADD verification_token VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE "user" DROP is_verified, DROP verification_token');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE user DROP is_verified, DROP verification_token');
        }
    }
}
