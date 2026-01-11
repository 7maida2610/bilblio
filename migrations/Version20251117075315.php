<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117075315 extends AbstractMigration
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
            $this->addSql('CREATE TABLE reading_goal (id SERIAL NOT NULL, user_id INT NOT NULL, goal_type VARCHAR(255) NOT NULL, target_value INT NOT NULL, current_value INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_CB8A837AA76ED395 ON reading_goal (user_id)');
            $this->addSql('CREATE TABLE reading_progress (id SERIAL NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, progress_percentage INT NOT NULL, last_read_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_completed BOOLEAN NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_74F6AFF6A76ED395 ON reading_progress (user_id)');
            $this->addSql('CREATE INDEX IDX_74F6AFF637D925CB ON reading_progress (livre_id)');
            $this->addSql('CREATE TABLE review (id SERIAL NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, rating INT NOT NULL, comment TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_794381C6A76ED395 ON review (user_id)');
            $this->addSql('CREATE INDEX IDX_794381C637D925CB ON review (livre_id)');
            $this->addSql('CREATE TABLE user_wishlist (user_id INT NOT NULL, livre_id INT NOT NULL, PRIMARY KEY(user_id, livre_id))');
            $this->addSql('CREATE INDEX IDX_7C6CCE31A76ED395 ON user_wishlist (user_id)');
            $this->addSql('CREATE INDEX IDX_7C6CCE3137D925CB ON user_wishlist (livre_id)');
            $this->addSql('CREATE TABLE user_owned_books (user_id INT NOT NULL, livre_id INT NOT NULL, PRIMARY KEY(user_id, livre_id))');
            $this->addSql('CREATE INDEX IDX_17977FD4A76ED395 ON user_owned_books (user_id)');
            $this->addSql('CREATE INDEX IDX_17977FD437D925CB ON user_owned_books (livre_id)');
            $this->addSql('CREATE TABLE user_favorite_authors (user_id INT NOT NULL, auteur_id INT NOT NULL, PRIMARY KEY(user_id, auteur_id))');
            $this->addSql('CREATE INDEX IDX_AE4DC6E2A76ED395 ON user_favorite_authors (user_id)');
            $this->addSql('CREATE INDEX IDX_AE4DC6E260BB6FE6 ON user_favorite_authors (auteur_id)');
            $this->addSql('ALTER TABLE reading_goal ADD CONSTRAINT FK_CB8A837AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
            $this->addSql('ALTER TABLE reading_progress ADD CONSTRAINT FK_74F6AFF6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
            $this->addSql('ALTER TABLE reading_progress ADD CONSTRAINT FK_74F6AFF637D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
            $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
            $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C637D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
            $this->addSql('ALTER TABLE user_wishlist ADD CONSTRAINT FK_7C6CCE31A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_wishlist ADD CONSTRAINT FK_7C6CCE3137D925CB FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_owned_books ADD CONSTRAINT FK_17977FD4A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_owned_books ADD CONSTRAINT FK_17977FD437D925CB FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_favorite_authors ADD CONSTRAINT FK_AE4DC6E2A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_favorite_authors ADD CONSTRAINT FK_AE4DC6E260BB6FE6 FOREIGN KEY (auteur_id) REFERENCES auteur (id) ON DELETE CASCADE');
        } else {
            // MySQL syntax
            $this->addSql('CREATE TABLE reading_goal (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, goal_type VARCHAR(255) NOT NULL, target_value INT NOT NULL, current_value INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, INDEX IDX_CB8A837AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE reading_progress (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, progress_percentage INT NOT NULL, last_read_at DATETIME NOT NULL, is_completed TINYINT(1) NOT NULL, INDEX IDX_74F6AFF6A76ED395 (user_id), INDEX IDX_74F6AFF637D925CB (livre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_794381C6A76ED395 (user_id), INDEX IDX_794381C637D925CB (livre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE user_wishlist (user_id INT NOT NULL, livre_id INT NOT NULL, INDEX IDX_7C6CCE31A76ED395 (user_id), INDEX IDX_7C6CCE3137D925CB (livre_id), PRIMARY KEY(user_id, livre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE user_owned_books (user_id INT NOT NULL, livre_id INT NOT NULL, INDEX IDX_17977FD4A76ED395 (user_id), INDEX IDX_17977FD437D925CB (livre_id), PRIMARY KEY(user_id, livre_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE user_favorite_authors (user_id INT NOT NULL, auteur_id INT NOT NULL, INDEX IDX_AE4DC6E2A76ED395 (user_id), INDEX IDX_AE4DC6E260BB6FE6 (auteur_id), PRIMARY KEY(user_id, auteur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE reading_goal ADD CONSTRAINT FK_CB8A837AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE reading_progress ADD CONSTRAINT FK_74F6AFF6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE reading_progress ADD CONSTRAINT FK_74F6AFF637D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
            $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C637D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
            $this->addSql('ALTER TABLE user_wishlist ADD CONSTRAINT FK_7C6CCE31A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_wishlist ADD CONSTRAINT FK_7C6CCE3137D925CB FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_owned_books ADD CONSTRAINT FK_17977FD4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_owned_books ADD CONSTRAINT FK_17977FD437D925CB FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_favorite_authors ADD CONSTRAINT FK_AE4DC6E2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_favorite_authors ADD CONSTRAINT FK_AE4DC6E260BB6FE6 FOREIGN KEY (auteur_id) REFERENCES auteur (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE reading_goal DROP CONSTRAINT FK_CB8A837AA76ED395');
            $this->addSql('ALTER TABLE reading_progress DROP CONSTRAINT FK_74F6AFF6A76ED395');
            $this->addSql('ALTER TABLE reading_progress DROP CONSTRAINT FK_74F6AFF637D925CB');
            $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C6A76ED395');
            $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C637D925CB');
            $this->addSql('ALTER TABLE user_wishlist DROP CONSTRAINT FK_7C6CCE31A76ED395');
            $this->addSql('ALTER TABLE user_wishlist DROP CONSTRAINT FK_7C6CCE3137D925CB');
            $this->addSql('ALTER TABLE user_owned_books DROP CONSTRAINT FK_17977FD4A76ED395');
            $this->addSql('ALTER TABLE user_owned_books DROP CONSTRAINT FK_17977FD437D925CB');
            $this->addSql('ALTER TABLE user_favorite_authors DROP CONSTRAINT FK_AE4DC6E2A76ED395');
            $this->addSql('ALTER TABLE user_favorite_authors DROP CONSTRAINT FK_AE4DC6E260BB6FE6');
            $this->addSql('DROP TABLE reading_goal');
            $this->addSql('DROP TABLE reading_progress');
            $this->addSql('DROP TABLE review');
            $this->addSql('DROP TABLE user_wishlist');
            $this->addSql('DROP TABLE user_owned_books');
            $this->addSql('DROP TABLE user_favorite_authors');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE reading_goal DROP FOREIGN KEY FK_CB8A837AA76ED395');
            $this->addSql('ALTER TABLE reading_progress DROP FOREIGN KEY FK_74F6AFF6A76ED395');
            $this->addSql('ALTER TABLE reading_progress DROP FOREIGN KEY FK_74F6AFF637D925CB');
            $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
            $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C637D925CB');
            $this->addSql('ALTER TABLE user_wishlist DROP FOREIGN KEY FK_7C6CCE31A76ED395');
            $this->addSql('ALTER TABLE user_wishlist DROP FOREIGN KEY FK_7C6CCE3137D925CB');
            $this->addSql('ALTER TABLE user_owned_books DROP FOREIGN KEY FK_17977FD4A76ED395');
            $this->addSql('ALTER TABLE user_owned_books DROP FOREIGN KEY FK_17977FD437D925CB');
            $this->addSql('ALTER TABLE user_favorite_authors DROP FOREIGN KEY FK_AE4DC6E2A76ED395');
            $this->addSql('ALTER TABLE user_favorite_authors DROP FOREIGN KEY FK_AE4DC6E260BB6FE6');
            $this->addSql('DROP TABLE reading_goal');
            $this->addSql('DROP TABLE reading_progress');
            $this->addSql('DROP TABLE review');
            $this->addSql('DROP TABLE user_wishlist');
            $this->addSql('DROP TABLE user_owned_books');
            $this->addSql('DROP TABLE user_favorite_authors');
        }
    }
}
