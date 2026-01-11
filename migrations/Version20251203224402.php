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
        return 'Create banner and user_banner_preference tables';
    }

    public function up(Schema $schema): void
    {
        // Create banners table
        $this->addSql('CREATE TABLE banners (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT, type VARCHAR(50) NOT NULL, position VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, start_date DATETIME, end_date DATETIME, image VARCHAR(255), link VARCHAR(255), link_text VARCHAR(100), priority SMALLINT, target_audience JSON, styling JSON, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8C4765D1DE12029B (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Create user_banner_preference table
        $this->addSql('CREATE TABLE user_banner_preference (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, banner_id INT NOT NULL, hidden TINYINT(1) NOT NULL, hidden_at DATETIME NOT NULL, INDEX IDX_67039197A76ED395 (user_id), INDEX IDX_67039197684EC833 (banner_id), UNIQUE INDEX unique_user_banner (user_id, banner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign keys
        $this->addSql('ALTER TABLE banners ADD CONSTRAINT FK_8C4765D1DE12029B FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_banner_preference ADD CONSTRAINT FK_67039197A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_banner_preference ADD CONSTRAINT FK_67039197684EC833 FOREIGN KEY (banner_id) REFERENCES banners (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_banner_preference DROP FOREIGN KEY FK_67039197684EC833');
        $this->addSql('ALTER TABLE banners DROP FOREIGN KEY FK_8C4765D1DE12029B');
        $this->addSql('ALTER TABLE user_banner_preference DROP FOREIGN KEY FK_67039197A76ED395');
        $this->addSql('DROP TABLE user_banner_preference');
        $this->addSql('DROP TABLE banners');
    }
}
