<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241118035728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comic CHANGE created created DATETIME NOT NULL');
        $this->addSql('ALTER TABLE search DROP FOREIGN KEY FK_B4F0DBA7D663094A');
        $this->addSql('DROP INDEX UNIQ_B4F0DBA7D663094A ON search');
        $this->addSql('ALTER TABLE search ADD entity VARCHAR(64) NOT NULL AFTER id, CHANGE comic_id entity_id INT NOT NULL AFTER entity');
        $this->addSql('CREATE UNIQUE INDEX entity_with_id ON search (entity, entity_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comic CHANGE created created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE search DROP entity, CHANGE entity_id comic_id INT NOT NULL');
        $this->addSql('ALTER TABLE search ADD CONSTRAINT FK_B4F0DBA7D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4F0DBA7D663094A ON search (comic_id)');
        $this->addSql('DROP INDEX entity_with_id ON search');
    }
}
