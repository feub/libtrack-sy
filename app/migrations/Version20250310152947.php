<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250310152947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_15996875E237E06 ON artist (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9E47031D97AE0266 ON `release` (barcode)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_15996875E237E06 ON artist');
        $this->addSql('DROP INDEX UNIQ_9E47031D97AE0266 ON `release`');
    }
}
