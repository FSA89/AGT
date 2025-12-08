<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124110615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_cloudflare_accounts ADD is_active TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_hreflang ADD is_active TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_neuro ADD is_active TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_page_types ADD is_active TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro ADD is_active TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_templates ADD is_active TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_cloudflare_accounts DROP is_active
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_hreflang DROP is_active
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_neuro DROP is_active
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_page_types DROP is_active
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro DROP is_active
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_templates DROP is_active
        SQL);
    }
}
