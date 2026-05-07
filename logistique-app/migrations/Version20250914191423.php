<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250914191423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE modele_vehicule RENAME INDEX uniq_41f7c11f90cd1a0f TO unique_modele_for_marque');
        $this->addSql('ALTER TABLE voyage_vehicule CHANGE id_voyage id_voyage INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE modele_vehicule RENAME INDEX unique_modele_for_marque TO UNIQ_41F7C11F90CD1A0F');
        $this->addSql('ALTER TABLE voyage_vehicule CHANGE id_voyage id_voyage INT DEFAULT NULL');
    }
}
