<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203231717 extends AbstractMigration
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
            $this->addSql('ALTER TABLE "user" ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL, ADD phone VARCHAR(255) DEFAULT NULL, ADD billing_address JSON DEFAULT NULL, ADD shipping_address JSON DEFAULT NULL');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE user ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL, ADD phone VARCHAR(255) DEFAULT NULL, ADD billing_address JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD shipping_address JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE "user" DROP first_name, DROP last_name, DROP phone, DROP billing_address, DROP shipping_address');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE user DROP first_name, DROP last_name, DROP phone, DROP billing_address, DROP shipping_address');
        }
    }
}
