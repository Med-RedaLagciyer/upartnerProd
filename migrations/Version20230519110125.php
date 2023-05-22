<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230519110125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Facture (id INT AUTO_INCREMENT NOT NULL, reclamation_id INT DEFAULT NULL, numFacture VARCHAR(255) DEFAULT NULL, montant DOUBLE PRECISION DEFAULT NULL, observation LONGTEXT DEFAULT NULL, dateFacture DATE DEFAULT NULL, created DATE DEFAULT NULL, updated DATE DEFAULT NULL, userCreated_id INT DEFAULT NULL, userUpdated_id INT DEFAULT NULL, INDEX IDX_313B5D8C5CC1316D (userCreated_id), INDEX IDX_313B5D8C942DE8DA (userUpdated_id), INDEX IDX_313B5D8C2D6BA2D9 (reclamation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Facture ADD CONSTRAINT FK_313B5D8C5CC1316D FOREIGN KEY (userCreated_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE Facture ADD CONSTRAINT FK_313B5D8C942DE8DA FOREIGN KEY (userUpdated_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE Facture ADD CONSTRAINT FK_313B5D8C2D6BA2D9 FOREIGN KEY (reclamation_id) REFERENCES Reclamation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY FK_313B5D8C5CC1316D');
        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY FK_313B5D8C942DE8DA');
        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY FK_313B5D8C2D6BA2D9');
        $this->addSql('DROP TABLE Facture');
    }
}
