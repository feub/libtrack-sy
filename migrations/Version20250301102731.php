<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250301102731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE release_artist (release_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_CFBBEC6AB12A727D (release_id), INDEX IDX_CFBBEC6AB7970CF8 (artist_id), PRIMARY KEY(release_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE release_artist ADD CONSTRAINT FK_CFBBEC6AB12A727D FOREIGN KEY (release_id) REFERENCES `release` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE release_artist ADD CONSTRAINT FK_CFBBEC6AB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE release_artist DROP FOREIGN KEY FK_CFBBEC6AB12A727D');
        $this->addSql('ALTER TABLE release_artist DROP FOREIGN KEY FK_CFBBEC6AB7970CF8');
        $this->addSql('DROP TABLE release_artist');
    }
}
