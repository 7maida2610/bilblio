<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203010152 extends AbstractMigration
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
            $this->addSql('CREATE TABLE book_reservations (id SERIAL NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, position INT NOT NULL, is_active BOOLEAN NOT NULL, notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_59AC6861A76ED395 ON book_reservations (user_id)');
            $this->addSql('CREATE INDEX IDX_59AC686137D925CB ON book_reservations (livre_id)');
            $this->addSql('ALTER TABLE book_reservations ADD CONSTRAINT FK_59AC6861A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
            $this->addSql('ALTER TABLE book_reservations ADD CONSTRAINT FK_59AC686137D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
        } else {
            // MySQL syntax
            $this->addSql('CREATE TABLE book_reservations (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', position INT NOT NULL, is_active TINYINT(1) NOT NULL, notified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_59AC6861A76ED395 (user_id), INDEX IDX_59AC686137D925CB (livre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE book_reservations ADD CONSTRAINT FK_59AC6861A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE book_reservations ADD CONSTRAINT FK_59AC686137D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE book_reservations DROP CONSTRAINT FK_59AC6861A76ED395');
            $this->addSql('ALTER TABLE book_reservations DROP CONSTRAINT FK_59AC686137D925CB');
            $this->addSql('DROP TABLE book_reservations');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE book_reservations DROP FOREIGN KEY FK_59AC6861A76ED395');
            $this->addSql('ALTER TABLE book_reservations DROP FOREIGN KEY FK_59AC686137D925CB');
            $this->addSql('DROP TABLE book_reservations');
        }
    }
}
