<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530131303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE genre (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_835033F85E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE genre_release (genre_id INT NOT NULL, release_id INT NOT NULL, INDEX IDX_A5C290E54296D31F (genre_id), INDEX IDX_A5C290E5B12A727D (release_id), PRIMARY KEY(genre_id, release_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE genre_release ADD CONSTRAINT FK_A5C290E54296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE genre_release ADD CONSTRAINT FK_A5C290E5B12A727D FOREIGN KEY (release_id) REFERENCES `release` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artist CHANGE slug slug VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genre_release DROP FOREIGN KEY FK_A5C290E54296D31F');
        $this->addSql('ALTER TABLE genre_release DROP FOREIGN KEY FK_A5C290E5B12A727D');
        $this->addSql('DROP TABLE genre');
        $this->addSql('DROP TABLE genre_release');
        $this->addSql('ALTER TABLE artist CHANGE slug slug VARCHAR(150) NOT NULL');
    }
}
