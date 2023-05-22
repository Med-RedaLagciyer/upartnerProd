<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230515142154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Reclamation (id INT AUTO_INCREMENT NOT NULL, observation LONGTEXT DEFAULT NULL, userCreated_id INT DEFAULT NULL, INDEX IDX_48FCEBD35CC1316D (userCreated_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Reclamation ADD CONSTRAINT FK_48FCEBD35CC1316D FOREIGN KEY (userCreated_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Reclamation DROP FOREIGN KEY FK_48FCEBD35CC1316D');
        $this->addSql('DROP TABLE Reclamation');
    }
}
