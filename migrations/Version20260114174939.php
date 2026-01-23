<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114174939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email (identifier CHAR(36) NOT NULL, email_type VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, body_json CLOB DEFAULT NULL, sent_date_time DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, id CHAR(36) NOT NULL, sent_by_id CHAR(36) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_E7927C74A45BB98C FOREIGN KEY (sent_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E7927C74A45BB98C ON email (sent_by_id)');
        $this->addSql('CREATE TABLE submission (id CHAR(36) NOT NULL, created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, iteration INTEGER NOT NULL, description VARCHAR(255) DEFAULT NULL, challenge_type VARCHAR(255) NOT NULL, evaluation_type VARCHAR(255) NOT NULL, report_filename VARCHAR(255) DEFAULT NULL, solution_filename VARCHAR(255) NOT NULL, evaluation_id VARCHAR(255) DEFAULT NULL, evaluation_status VARCHAR(255) DEFAULT NULL, evaluation_version INTEGER DEFAULT NULL, evaluation_error VARCHAR(255) DEFAULT NULL, evaluation_error_log VARCHAR(255) DEFAULT NULL, evaluation_folder VARCHAR(255) DEFAULT NULL, evaluation_score DOUBLE PRECISION DEFAULT NULL, evaluation_report_filename VARCHAR(255) DEFAULT NULL, user_id CHAR(36) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_DB055AF3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DB055AF3A76ED395 ON submission (user_id)');
        $this->addSql('CREATE TABLE user (prefer_anonymity BOOLEAN DEFAULT 0 NOT NULL, hide_from_leaderboard BOOLEAN DEFAULT 0 NOT NULL, is_admin BOOLEAN DEFAULT 0 NOT NULL, receive_admin_notifications BOOLEAN DEFAULT 0 NOT NULL, id CHAR(36) NOT NULL, created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, authentication_hash VARCHAR(255) DEFAULT NULL, team_name VARCHAR(255) DEFAULT NULL, affiliation VARCHAR(255) DEFAULT NULL, contact_name VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, webpage VARCHAR(255) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE email');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE user');
    }
}
