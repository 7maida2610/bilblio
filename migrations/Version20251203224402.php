<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203224402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_banner_preference table (banners table already exists)';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax - banners table already exists in Version20251128154844
            // Only create user_banner_preference table
            $this->addSql('CREATE TABLE user_banner_preference (id SERIAL NOT NULL, user_id INT NOT NULL, banner_id INT NOT NULL, hidden BOOLEAN NOT NULL, hidden_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_67039197A76ED395 ON user_banner_preference (user_id)');
            $this->addSql('CREATE INDEX IDX_67039197684EC833 ON user_banner_preference (banner_id)');
            $this->addSql('CREATE UNIQUE INDEX unique_user_banner ON user_banner_preference (user_id, banner_id)');
            $this->addSql('ALTER TABLE user_banner_preference ADD CONSTRAINT FK_67039197A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_banner_preference ADD CONSTRAINT FK_67039197684EC833 FOREIGN KEY (banner_id) REFERENCES banners (id) ON DELETE CASCADE');
        } else {
            // MySQL syntax - banners table already exists in Version20251128154844
            // Only create user_banner_preference table
            $this->addSql('CREATE TABLE user_banner_preference (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, banner_id INT NOT NULL, hidden TINYINT(1) NOT NULL, hidden_at DATETIME NOT NULL, INDEX IDX_67039197A76ED395 (user_id), INDEX IDX_67039197684EC833 (banner_id), UNIQUE INDEX unique_user_banner (user_id, banner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE user_banner_preference ADD CONSTRAINT FK_67039197A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_banner_preference ADD CONSTRAINT FK_67039197684EC833 FOREIGN KEY (banner_id) REFERENCES banners (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax - banners table is managed by Version20251128154844
            $this->addSql('ALTER TABLE user_banner_preference DROP CONSTRAINT FK_67039197684EC833');
            $this->addSql('ALTER TABLE user_banner_preference DROP CONSTRAINT FK_67039197A76ED395');
            $this->addSql('DROP TABLE user_banner_preference');
        } else {
            // MySQL syntax - banners table is managed by Version20251128154844
            $this->addSql('ALTER TABLE user_banner_preference DROP FOREIGN KEY FK_67039197684EC833');
            $this->addSql('ALTER TABLE user_banner_preference DROP FOREIGN KEY FK_67039197A76ED395');
            $this->addSql('DROP TABLE user_banner_preference');
        }
    }
}
