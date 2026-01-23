<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116094628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__email AS SELECT identifier, email_type, link, body_json, sent_date_time, read_at, id, sent_by_id FROM email');
        $this->addSql('DROP TABLE email');
        $this->addSql('CREATE TABLE email (identifier CHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, body CLOB DEFAULT NULL, sent_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, id CHAR(36) NOT NULL, sent_by_id CHAR(36) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_E7927C74A45BB98C FOREIGN KEY (sent_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO email (identifier, type, link, body, sent_at, read_at, id, sent_by_id) SELECT identifier, email_type, link, body_json, sent_date_time, read_at, id, sent_by_id FROM __temp__email');
        $this->addSql('DROP TABLE __temp__email');
        $this->addSql('CREATE INDEX IDX_E7927C74A45BB98C ON email (sent_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__email AS SELECT identifier, type, link, body, sent_at, read_at, id, sent_by_id FROM email');
        $this->addSql('DROP TABLE email');
        $this->addSql('CREATE TABLE email (identifier CHAR(36) NOT NULL, email_type VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, body_json CLOB DEFAULT NULL, sent_date_time DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, id CHAR(36) NOT NULL, sent_by_id CHAR(36) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_E7927C74A45BB98C FOREIGN KEY (sent_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO email (identifier, email_type, link, body_json, sent_date_time, read_at, id, sent_by_id) SELECT identifier, type, link, body, sent_at, read_at, id, sent_by_id FROM __temp__email');
        $this->addSql('DROP TABLE __temp__email');
        $this->addSql('CREATE INDEX IDX_E7927C74A45BB98C ON email (sent_by_id)');
    }
}
