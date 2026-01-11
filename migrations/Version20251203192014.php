<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203192014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_method column to orders table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('orders');
        if (!$table->hasColumn('payment_method')) {
            $table->addColumn('payment_method', 'string', ['length' => 50, 'default' => 'stripe', 'notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('orders');
        if ($table->hasColumn('payment_method')) {
            $table->dropColumn('payment_method');
        }
    }
}
