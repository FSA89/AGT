<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251119105921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tbl_articles (id INT AUTO_INCREMENT NOT NULL, task_id INT DEFAULT NULL, task_custom_id VARCHAR(50) DEFAULT NULL, title LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, content LONGTEXT DEFAULT NULL, rating INT DEFAULT NULL, status VARCHAR(50) DEFAULT 'ready' NOT NULL, domain_url VARCHAR(255) DEFAULT NULL, INDEX IDX_E8EED7458DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_articles ADD CONSTRAINT FK_E8EED7458DB60186 FOREIGN KEY (task_id) REFERENCES tbl_tasks (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tbl_domains
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tbl_domains (id INT AUTO_INCREMENT NOT NULL, domain VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_D728B7E9A7A91E0B (domain), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_articles DROP FOREIGN KEY FK_E8EED7458DB60186
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tbl_articles
        SQL);
    }
}
