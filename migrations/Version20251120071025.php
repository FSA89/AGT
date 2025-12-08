<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120071025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tbl_sites (id INT AUTO_INCREMENT NOT NULL, article_id INT DEFAULT NULL, template_id INT DEFAULT NULL, registrar VARCHAR(50) DEFAULT NULL, status_registration VARCHAR(50) DEFAULT 'pending', webmaster VARCHAR(50) DEFAULT NULL, y_txt_status VARCHAR(50) DEFAULT 'pending', indexing_status VARCHAR(50) DEFAULT 'pending', cf_email VARCHAR(255) DEFAULT NULL, cf_api_key VARCHAR(255) DEFAULT NULL, status_cf VARCHAR(50) DEFAULT 'pending', status_ns_update VARCHAR(50) DEFAULT 'pending', ns1 VARCHAR(255) DEFAULT NULL, ns2 VARCHAR(255) DEFAULT NULL, ns_status VARCHAR(50) DEFAULT 'pending', status_proxy VARCHAR(50) DEFAULT 'pending', full_cycle_status VARCHAR(50) DEFAULT 'pending', upload_status VARCHAR(50) DEFAULT 'pending', publish_date DATE DEFAULT NULL, site_status VARCHAR(50) DEFAULT 'pending', UNIQUE INDEX UNIQ_1264E07C7294869C (article_id), INDEX IDX_1264E07C5DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites ADD CONSTRAINT FK_1264E07C7294869C FOREIGN KEY (article_id) REFERENCES tbl_articles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites ADD CONSTRAINT FK_1264E07C5DA0FB8 FOREIGN KEY (template_id) REFERENCES tbl_templates (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites DROP FOREIGN KEY FK_1264E07C7294869C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites DROP FOREIGN KEY FK_1264E07C5DA0FB8
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tbl_sites
        SQL);
    }
}
