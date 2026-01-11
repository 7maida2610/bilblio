<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128154844 extends AbstractMigration
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
            // PostgreSQL syntax - Use IF NOT EXISTS to avoid errors if tables already exist
            $this->addSql('CREATE TABLE IF NOT EXISTS banners (id SERIAL NOT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, content TEXT DEFAULT NULL, type VARCHAR(50) NOT NULL, position VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, link_text VARCHAR(100) DEFAULT NULL, priority SMALLINT DEFAULT NULL, target_audience JSON DEFAULT NULL, styling JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_250F2568B03A8386 ON banners (created_by_id)');
            $this->addSql('CREATE TABLE IF NOT EXISTS cart_items (id SERIAL NOT NULL, cart_id INT NOT NULL, livre_id INT NOT NULL, quantity INT NOT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_BEF484451AD5CDBF ON cart_items (cart_id)');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_BEF4844537D925CB ON cart_items (livre_id)');
            $this->addSql('CREATE TABLE IF NOT EXISTS carts (id SERIAL NOT NULL, user_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_4E004AACA76ED395 ON carts (user_id)');
            $this->addSql('CREATE TABLE IF NOT EXISTS loans (id SERIAL NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, status VARCHAR(20) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, loan_start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, returned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, notes TEXT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_82C24DBCA76ED395 ON loans (user_id)');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_82C24DBC37D925CB ON loans (livre_id)');
            $this->addSql('CREATE TABLE IF NOT EXISTS order_items (id SERIAL NOT NULL, order_id INT NOT NULL, livre_id INT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_62809DB08D9F6D38 ON order_items (order_id)');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_62809DB037D925CB ON order_items (livre_id)');
            $this->addSql('CREATE TABLE IF NOT EXISTS orders (id SERIAL NOT NULL, user_id INT NOT NULL, order_number VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, shipping_address JSON DEFAULT NULL, billing_address JSON DEFAULT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, shipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_E52FFDEE551F0F81 ON orders (order_number)');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_E52FFDEEA76ED395 ON orders (user_id)');
            // Use DO block to handle foreign key constraints gracefully
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_250F2568B03A8386\') THEN ALTER TABLE banners ADD CONSTRAINT FK_250F2568B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_BEF484451AD5CDBF\') THEN ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484451AD5CDBF FOREIGN KEY (cart_id) REFERENCES carts (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_BEF4844537D925CB\') THEN ALTER TABLE cart_items ADD CONSTRAINT FK_BEF4844537D925CB FOREIGN KEY (livre_id) REFERENCES livre (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_4E004AACA76ED395\') THEN ALTER TABLE carts ADD CONSTRAINT FK_4E004AACA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_82C24DBCA76ED395\') THEN ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_82C24DBC37D925CB\') THEN ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC37D925CB FOREIGN KEY (livre_id) REFERENCES livre (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_62809DB08D9F6D38\') THEN ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_62809DB037D925CB\') THEN ALTER TABLE order_items ADD CONSTRAINT FK_62809DB037D925CB FOREIGN KEY (livre_id) REFERENCES livre (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'FK_E52FFDEEA76ED395\') THEN ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id); END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$;');
            $this->addSql('ALTER TABLE reading_goal DROP COLUMN IF EXISTS description');
            $this->addSql('ALTER TABLE reading_goal DROP COLUMN IF EXISTS priority');
            // Use IF NOT EXISTS to avoid errors if columns already exist
            $this->addSql('ALTER TABLE review ADD COLUMN IF NOT EXISTS images JSON DEFAULT NULL');
            $this->addSql('ALTER TABLE review ADD COLUMN IF NOT EXISTS verified BOOLEAN DEFAULT NULL');
            $this->addSql('ALTER TABLE review ADD COLUMN IF NOT EXISTS helpful INT DEFAULT NULL');
            $this->addSql('ALTER TABLE review ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
            // Set default values for existing rows if columns were just added
            $this->addSql('UPDATE review SET verified = false WHERE verified IS NULL');
            $this->addSql('UPDATE review SET helpful = 0 WHERE helpful IS NULL');
            // Now make columns NOT NULL (only if they are currently nullable)
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'review\' AND column_name = \'verified\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE review ALTER COLUMN verified SET NOT NULL;
                END IF;
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'review\' AND column_name = \'helpful\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE review ALTER COLUMN helpful SET NOT NULL;
                END IF;
            END $$;');
            $this->addSql('ALTER TABLE "user" ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at SET DEFAULT NULL');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at SET DEFAULT NULL');
            // Only set NOT NULL if column is currently nullable
            $this->addSql('DO $$ BEGIN 
                IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = \'user\' AND column_name = \'is_verified\' AND is_nullable = \'YES\') THEN
                    ALTER TABLE "user" ALTER COLUMN is_verified SET NOT NULL;
                END IF;
            END $$;');
        } else {
            // MySQL syntax
            $this->addSql('CREATE TABLE banners (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, type VARCHAR(50) NOT NULL, position VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, link_text VARCHAR(100) DEFAULT NULL, priority SMALLINT DEFAULT NULL, target_audience JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', styling JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_250F2568B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE cart_items (id INT AUTO_INCREMENT NOT NULL, cart_id INT NOT NULL, livre_id INT NOT NULL, quantity INT NOT NULL, added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BEF484451AD5CDBF (cart_id), INDEX IDX_BEF4844537D925CB (livre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE carts (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4E004AACA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE loans (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, livre_id INT NOT NULL, status VARCHAR(20) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', approved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', loan_start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', due_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', returned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', cancelled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes LONGTEXT DEFAULT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_82C24DBCA76ED395 (user_id), INDEX IDX_82C24DBC37D925CB (livre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, livre_id INT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, INDEX IDX_62809DB08D9F6D38 (order_id), INDEX IDX_62809DB037D925CB (livre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, order_number VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, shipping_address JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', billing_address JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', shipped_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E52FFDEE551F0F81 (order_number), INDEX IDX_E52FFDEEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE banners ADD CONSTRAINT FK_250F2568B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF484451AD5CDBF FOREIGN KEY (cart_id) REFERENCES carts (id)');
            $this->addSql('ALTER TABLE cart_items ADD CONSTRAINT FK_BEF4844537D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
            $this->addSql('ALTER TABLE carts ADD CONSTRAINT FK_4E004AACA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC37D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
            $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
            $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB037D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
            $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('ALTER TABLE reading_goal DROP description, DROP priority');
            $this->addSql('ALTER TABLE review ADD images JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD verified TINYINT(1) NOT NULL, ADD helpful INT NOT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            $this->addSql('ALTER TABLE user ADD profile_picture VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_verified is_verified TINYINT(1) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isPostgres = $platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform;
        
        if ($isPostgres) {
            // PostgreSQL syntax
            $this->addSql('ALTER TABLE banners DROP CONSTRAINT FK_250F2568B03A8386');
            $this->addSql('ALTER TABLE cart_items DROP CONSTRAINT FK_BEF484451AD5CDBF');
            $this->addSql('ALTER TABLE cart_items DROP CONSTRAINT FK_BEF4844537D925CB');
            $this->addSql('ALTER TABLE carts DROP CONSTRAINT FK_4E004AACA76ED395');
            $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBCA76ED395');
            $this->addSql('ALTER TABLE loans DROP CONSTRAINT FK_82C24DBC37D925CB');
            $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_62809DB08D9F6D38');
            $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_62809DB037D925CB');
            $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEEA76ED395');
            $this->addSql('DROP TABLE banners');
            $this->addSql('DROP TABLE cart_items');
            $this->addSql('DROP TABLE carts');
            $this->addSql('DROP TABLE loans');
            $this->addSql('DROP TABLE order_items');
            $this->addSql('DROP TABLE orders');
            $this->addSql('ALTER TABLE reading_goal ADD description TEXT DEFAULT NULL');
            $this->addSql('ALTER TABLE reading_goal ADD priority VARCHAR(20) NOT NULL');
            $this->addSql('ALTER TABLE review DROP images');
            $this->addSql('ALTER TABLE review DROP verified');
            $this->addSql('ALTER TABLE review DROP helpful');
            $this->addSql('ALTER TABLE review DROP updated_at');
            $this->addSql('ALTER TABLE "user" DROP profile_picture');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN is_verified DROP DEFAULT');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at DROP DEFAULT');
            $this->addSql('ALTER TABLE "user" ALTER COLUMN updated_at DROP DEFAULT');
        } else {
            // MySQL syntax
            $this->addSql('ALTER TABLE banners DROP FOREIGN KEY FK_250F2568B03A8386');
            $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_BEF484451AD5CDBF');
            $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_BEF4844537D925CB');
            $this->addSql('ALTER TABLE carts DROP FOREIGN KEY FK_4E004AACA76ED395');
            $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBCA76ED395');
            $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC37D925CB');
            $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
            $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB037D925CB');
            $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
            $this->addSql('DROP TABLE banners');
            $this->addSql('DROP TABLE cart_items');
            $this->addSql('DROP TABLE carts');
            $this->addSql('DROP TABLE loans');
            $this->addSql('DROP TABLE order_items');
            $this->addSql('DROP TABLE orders');
            $this->addSql('ALTER TABLE reading_goal ADD description LONGTEXT DEFAULT NULL, ADD priority VARCHAR(20) NOT NULL');
            $this->addSql('ALTER TABLE review DROP images, DROP verified, DROP helpful, DROP updated_at');
            $this->addSql('ALTER TABLE user DROP profile_picture, CHANGE is_verified is_verified TINYINT(1) DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        }
    }
}
