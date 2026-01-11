<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117072736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add audit columns as nullable first
        $this->addSql('ALTER TABLE auteur ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE categorie ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE editeur ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE livre ADD created_by_id INT DEFAULT NULL, ADD updated_by_id INT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Populate existing records with current timestamp
        $now = date('Y-m-d H:i:s');
        $this->addSql("UPDATE auteur SET created_at = '$now', updated_at = '$now' WHERE created_at IS NULL");
        $this->addSql("UPDATE categorie SET created_at = '$now', updated_at = '$now' WHERE created_at IS NULL");
        $this->addSql("UPDATE editeur SET created_at = '$now', updated_at = '$now' WHERE created_at IS NULL");
        $this->addSql("UPDATE livre SET created_at = '$now', updated_at = '$now' WHERE created_at IS NULL");

        // Now make the columns NOT NULL
        $this->addSql('ALTER TABLE auteur MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', MODIFY updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE categorie MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', MODIFY updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE editeur MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', MODIFY updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE livre MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', MODIFY updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Add foreign key constraints and indexes
        $this->addSql('ALTER TABLE auteur ADD CONSTRAINT FK_55AB140B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE auteur ADD CONSTRAINT FK_55AB140896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_55AB140B03A8386 ON auteur (created_by_id)');
        $this->addSql('CREATE INDEX IDX_55AB140896DBBDE ON auteur (updated_by_id)');

        $this->addSql('ALTER TABLE categorie ADD CONSTRAINT FK_497DD634B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE categorie ADD CONSTRAINT FK_497DD634896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_497DD634B03A8386 ON categorie (created_by_id)');
        $this->addSql('CREATE INDEX IDX_497DD634896DBBDE ON categorie (updated_by_id)');

        $this->addSql('ALTER TABLE editeur ADD CONSTRAINT FK_5A747EFB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE editeur ADD CONSTRAINT FK_5A747EF896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_5A747EFB03A8386 ON editeur (created_by_id)');
        $this->addSql('CREATE INDEX IDX_5A747EF896DBBDE ON editeur (updated_by_id)');

        $this->addSql('ALTER TABLE livre ADD CONSTRAINT FK_AC634F99B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE livre ADD CONSTRAINT FK_AC634F99896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AC634F99B03A8386 ON livre (created_by_id)');
        $this->addSql('CREATE INDEX IDX_AC634F99896DBBDE ON livre (updated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE auteur DROP FOREIGN KEY FK_55AB140B03A8386');
        $this->addSql('ALTER TABLE auteur DROP FOREIGN KEY FK_55AB140896DBBDE');
        $this->addSql('DROP INDEX IDX_55AB140B03A8386 ON auteur');
        $this->addSql('DROP INDEX IDX_55AB140896DBBDE ON auteur');
        $this->addSql('ALTER TABLE auteur DROP created_by_id, DROP updated_by_id, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE categorie DROP FOREIGN KEY FK_497DD634B03A8386');
        $this->addSql('ALTER TABLE categorie DROP FOREIGN KEY FK_497DD634896DBBDE');
        $this->addSql('DROP INDEX IDX_497DD634B03A8386 ON categorie');
        $this->addSql('DROP INDEX IDX_497DD634896DBBDE ON categorie');
        $this->addSql('ALTER TABLE categorie DROP created_by_id, DROP updated_by_id, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE editeur DROP FOREIGN KEY FK_5A747EFB03A8386');
        $this->addSql('ALTER TABLE editeur DROP FOREIGN KEY FK_5A747EF896DBBDE');
        $this->addSql('DROP INDEX IDX_5A747EFB03A8386 ON editeur');
        $this->addSql('DROP INDEX IDX_5A747EF896DBBDE ON editeur');
        $this->addSql('ALTER TABLE editeur DROP created_by_id, DROP updated_by_id, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE livre DROP FOREIGN KEY FK_AC634F99B03A8386');
        $this->addSql('ALTER TABLE livre DROP FOREIGN KEY FK_AC634F99896DBBDE');
        $this->addSql('DROP INDEX IDX_AC634F99B03A8386 ON livre');
        $this->addSql('DROP INDEX IDX_AC634F99896DBBDE ON livre');
        $this->addSql('ALTER TABLE livre DROP created_by_id, DROP updated_by_id, DROP created_at, DROP updated_at');
    }
}
