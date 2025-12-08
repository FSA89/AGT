<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120084221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites ADD cf_account_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites ADD CONSTRAINT FK_1264E07CE88527D4 FOREIGN KEY (cf_account_id) REFERENCES tbl_cloudflare_accounts (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1264E07CE88527D4 ON tbl_sites (cf_account_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites DROP FOREIGN KEY FK_1264E07CE88527D4
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1264E07CE88527D4 ON tbl_sites
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_sites DROP cf_account_id
        SQL);
    }
}
