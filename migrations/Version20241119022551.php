<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241119022551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a FULLTEXT index to Search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE search ADD FULLTEXT FULLTEXT_CONTENT_SEARCH (content)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX FULLTEXT_CONTENT_SEARCH ON search');
    }
}
