<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250703090712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE release_user (release_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C064665EB12A727D (release_id), INDEX IDX_C064665EA76ED395 (user_id), PRIMARY KEY(release_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE release_user ADD CONSTRAINT FK_C064665EB12A727D FOREIGN KEY (release_id) REFERENCES `release` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE release_user ADD CONSTRAINT FK_C064665EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE release_user DROP FOREIGN KEY FK_C064665EB12A727D');
        $this->addSql('ALTER TABLE release_user DROP FOREIGN KEY FK_C064665EA76ED395');
        $this->addSql('DROP TABLE release_user');
    }
}
