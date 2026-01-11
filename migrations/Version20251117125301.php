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
            $this->addSql('ALTER TABLE "user" ADD is_verified BOOLEAN NOT NULL, ADD verification_token VARCHAR(255) DEFAULT NULL');
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
