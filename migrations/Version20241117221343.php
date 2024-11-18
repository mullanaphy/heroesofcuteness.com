<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241117221343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `character` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, age INT NOT NULL, biography LONGTEXT NOT NULL, source VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE character_comic (character_id INT NOT NULL, comic_id INT NOT NULL, INDEX IDX_A5BD42C81136BE75 (character_id), INDEX IDX_A5BD42C8D663094A (comic_id), PRIMARY KEY(character_id, comic_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE character_comic ADD CONSTRAINT FK_A5BD42C81136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE character_comic ADD CONSTRAINT FK_A5BD42C8D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE character_comic DROP FOREIGN KEY FK_A5BD42C81136BE75');
        $this->addSql('ALTER TABLE character_comic DROP FOREIGN KEY FK_A5BD42C8D663094A');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE character_comic');
    }
}
