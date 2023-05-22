<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230516104532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reclamation_user (reclamation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8CDC51262D6BA2D9 (reclamation_id), INDEX IDX_8CDC5126A76ED395 (user_id), PRIMARY KEY(reclamation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reclamation_user ADD CONSTRAINT FK_8CDC51262D6BA2D9 FOREIGN KEY (reclamation_id) REFERENCES Reclamation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation_user ADD CONSTRAINT FK_8CDC5126A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation ADD objet VARCHAR(255) DEFAULT NULL, ADD created DATE DEFAULT NULL, ADD updated DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reclamation_user DROP FOREIGN KEY FK_8CDC51262D6BA2D9');
        $this->addSql('ALTER TABLE reclamation_user DROP FOREIGN KEY FK_8CDC5126A76ED395');
        $this->addSql('DROP TABLE reclamation_user');
        $this->addSql('ALTER TABLE Reclamation DROP objet, DROP created, DROP updated');
    }
}
