<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522154150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE PartenaireValide (id INT AUTO_INCREMENT NOT NULL, partenaireId INT DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, societe VARCHAR(255) DEFAULT NULL, ICE VARCHAR(255) DEFAULT NULL, tel1 VARCHAR(255) DEFAULT NULL, tel2 VARCHAR(255) DEFAULT NULL, contact1 VARCHAR(255) DEFAULT NULL, contact2 VARCHAR(255) DEFAULT NULL, mail1 VARCHAR(255) DEFAULT NULL, pays VARCHAR(255) DEFAULT NULL, ville VARCHAR(255) DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, created DATE DEFAULT NULL, userCreated_id INT DEFAULT NULL, INDEX IDX_911C418F5CC1316D (userCreated_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE PartenaireValide ADD CONSTRAINT FK_911C418F5CC1316D FOREIGN KEY (userCreated_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE PartenaireValide DROP FOREIGN KEY FK_911C418F5CC1316D');
        $this->addSql('DROP TABLE PartenaireValide');
    }
}
