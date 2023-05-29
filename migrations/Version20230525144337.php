<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230525144337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Statut (id INT AUTO_INCREMENT NOT NULL, designation VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE facture ADD statut_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_313B5D8CF6203804 FOREIGN KEY (statut_id) REFERENCES Statut (id)');
        $this->addSql('CREATE INDEX IDX_313B5D8CF6203804 ON facture (statut_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Facture DROP FOREIGN KEY FK_313B5D8CF6203804');
        $this->addSql('DROP TABLE Statut');
        $this->addSql('DROP INDEX IDX_313B5D8CF6203804 ON Facture');
        $this->addSql('ALTER TABLE Facture DROP statut_id');
    }
}
