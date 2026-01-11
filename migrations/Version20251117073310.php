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
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

            // Populate existing records with current timestamp
            $now = date('Y-m-d H:i:s');
            $this->addSql("UPDATE \"user\" SET created_at = '$now', updated_at = '$now' WHERE created_at IS NULL");

            // Now make the columns NOT NULL
            $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at SET NOT NULL');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at SET NOT NULL');
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
