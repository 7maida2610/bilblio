<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109091325 extends AbstractMigration
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
            // PostgreSQL syntax - ALTER COLUMN instead of CHANGE
            $this->addSql('ALTER TABLE livre ALTER COLUMN auteur_id SET NOT NULL');
            $this->addSql('ALTER TABLE livre ALTER COLUMN categorie_id SET NOT NULL');
            $this->addSql('ALTER TABLE livre ALTER COLUMN editeur_id SET NOT NULL');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE livre CHANGE auteur_id auteur_id INT NOT NULL, CHANGE categorie_id categorie_id INT NOT NULL, CHANGE editeur_id editeur_id INT NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE livre ALTER COLUMN auteur_id DROP NOT NULL');
            $this->addSql('ALTER TABLE livre ALTER COLUMN categorie_id DROP NOT NULL');
            $this->addSql('ALTER TABLE livre ALTER COLUMN editeur_id DROP NOT NULL');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE livre CHANGE auteur_id auteur_id INT DEFAULT NULL, CHANGE categorie_id categorie_id INT DEFAULT NULL, CHANGE editeur_id editeur_id INT DEFAULT NULL');
        }
    }
}
