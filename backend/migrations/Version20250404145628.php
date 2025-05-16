<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404145628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shelf (id INT AUTO_INCREMENT NOT NULL, location VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `release` ADD shelf_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `release` ADD CONSTRAINT FK_9E47031D7C12FBC0 FOREIGN KEY (shelf_id) REFERENCES shelf (id)');
        $this->addSql('CREATE INDEX IDX_9E47031D7C12FBC0 ON `release` (shelf_id)');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `release` DROP FOREIGN KEY FK_9E47031D7C12FBC0');
        $this->addSql('DROP TABLE shelf');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('DROP INDEX IDX_9E47031D7C12FBC0 ON `release`');
        $this->addSql('ALTER TABLE `release` DROP shelf_id');
    }
}
