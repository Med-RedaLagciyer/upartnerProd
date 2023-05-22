<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230516151255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reclamation ADD userUpdated_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_48FCEBD3942DE8DA FOREIGN KEY (userUpdated_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_48FCEBD3942DE8DA ON reclamation (userUpdated_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Reclamation DROP FOREIGN KEY FK_48FCEBD3942DE8DA');
        $this->addSql('DROP INDEX IDX_48FCEBD3942DE8DA ON Reclamation');
        $this->addSql('ALTER TABLE Reclamation DROP userUpdated_id');
    }
}
