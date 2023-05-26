<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230525071319 extends AbstractMigration
{
    private const TABLE_NAME = 'server';
    private const COLUMN_NAME = 'disallow_firefox';
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME,Types::BOOLEAN)
            ->setDefault(null)
            ->setNotnull(false);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropColumn(self::COLUMN_NAME);
    }
}
