<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205124757 extends AbstractMigration
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
            $this->addSql('ALTER TABLE livre ADD COLUMN IF NOT EXISTS stock_vente INT DEFAULT 0');
            $this->addSql('ALTER TABLE livre ADD COLUMN IF NOT EXISTS stock_emprunt INT DEFAULT 0');
            // Set NOT NULL only if columns are currently nullable
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'livre\' AND column_name = \'stock_vente\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE livre ALTER COLUMN stock_vente SET NOT NULL;
                END IF;
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'livre\' AND column_name = \'stock_emprunt\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE livre ALTER COLUMN stock_emprunt SET NOT NULL;
                END IF;
            END $$;');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE livre ADD stock_vente INT DEFAULT 0 NOT NULL, ADD stock_emprunt INT DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE livre DROP COLUMN IF EXISTS stock_vente');
            $this->addSql('ALTER TABLE livre DROP COLUMN IF EXISTS stock_emprunt');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE livre DROP stock_vente, DROP stock_emprunt');
        }
    }
}
