<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251119092933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tbl_tasks (id INT AUTO_INCREMENT NOT NULL, page_type_id INT DEFAULT NULL, hreflang_id INT DEFAULT NULL, scheme_neuro_id INT DEFAULT NULL, main_keyword VARCHAR(255) DEFAULT NULL, keywords LONGTEXT DEFAULT NULL, competitor_urls LONGTEXT DEFAULT NULL, competitor_structures LONGTEXT DEFAULT NULL, count INT DEFAULT NULL, count_done INT DEFAULT 0 NOT NULL, status VARCHAR(50) DEFAULT 'generate' NOT NULL, query LONGTEXT DEFAULT NULL, INDEX IDX_FE3C2F883F2C6706 (page_type_id), INDEX IDX_FE3C2F88E5111E39 (hreflang_id), INDEX IDX_FE3C2F88EB7D9426 (scheme_neuro_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_tasks ADD CONSTRAINT FK_FE3C2F883F2C6706 FOREIGN KEY (page_type_id) REFERENCES tbl_page_types (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_tasks ADD CONSTRAINT FK_FE3C2F88E5111E39 FOREIGN KEY (hreflang_id) REFERENCES tbl_hreflang (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_tasks ADD CONSTRAINT FK_FE3C2F88EB7D9426 FOREIGN KEY (scheme_neuro_id) REFERENCES tbl_scheme_neuro (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_tasks DROP FOREIGN KEY FK_FE3C2F883F2C6706
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_tasks DROP FOREIGN KEY FK_FE3C2F88E5111E39
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_tasks DROP FOREIGN KEY FK_FE3C2F88EB7D9426
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tbl_tasks
        SQL);
    }
}
