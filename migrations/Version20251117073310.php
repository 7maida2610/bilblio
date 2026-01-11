<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117073310 extends AbstractMigration
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
            $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

            // Populate existing records with current timestamp (only if NULL)
            $now = date('Y-m-d H:i:s');
            $this->addSql("UPDATE \"user\" SET created_at = '$now' WHERE created_at IS NULL");
            $this->addSql("UPDATE \"user\" SET updated_at = '$now' WHERE updated_at IS NULL");

            // Now make the columns NOT NULL (only if they are currently nullable)
            // Use DO block to handle gracefully if columns are already NOT NULL
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'user\' AND column_name = \'created_at\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE "user" ALTER COLUMN created_at SET NOT NULL;
                END IF;
            END $$;');
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'user\' AND column_name = \'updated_at\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE "user" ALTER COLUMN updated_at SET NOT NULL;
                END IF;
            END $$;');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

            // Populate existing records with current timestamp
            $now = date('Y-m-d H:i:s');
            $this->addSql("UPDATE user SET created_at = '$now', updated_at = '$now' WHERE created_at IS NULL");

            // Now make the columns NOT NULL
            $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', MODIFY updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE "user" DROP created_at');
            $this->addSql('ALTER TABLE "user" DROP updated_at');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE user DROP created_at, DROP updated_at');
        }
    }
}
