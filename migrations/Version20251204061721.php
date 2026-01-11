<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204061721 extends AbstractMigration
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
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE loans ADD approved_by_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_82C24DBC2D234F6A ON loans (approved_by_id)');
            $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC2D234F6A FOREIGN KEY (approved_by_id) REFERENCES "user" (id)');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE loans ADD approved_by_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC2D234F6A FOREIGN KEY (approved_by_id) REFERENCES user (id)');
            $this->addSql('CREATE INDEX IDX_82C24DBC2D234F6A ON loans (approved_by_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBC2D234F6A');
            $this->addSql('DROP INDEX IDX_82C24DBC2D234F6A');
            $this->addSql('ALTER TABLE loans DROP approved_by_id');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC2D234F6A');
            $this->addSql('DROP INDEX IDX_82C24DBC2D234F6A ON loans');
            $this->addSql('ALTER TABLE loans DROP approved_by_id');
        }
    }
}
