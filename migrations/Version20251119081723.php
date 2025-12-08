<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251119081723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tbl_scheme_neuro (id INT AUTO_INCREMENT NOT NULL, structure_analyzer_id INT DEFAULT NULL, meta_header_generator_id INT DEFAULT NULL, writer_id INT DEFAULT NULL, meta_corrector_id INT DEFAULT NULL, text_corrector_id INT DEFAULT NULL, scheme_neuro LONGTEXT NOT NULL, scheme_neuro_full LONGTEXT NOT NULL, INDEX IDX_1CDCF0BAB254F930 (structure_analyzer_id), INDEX IDX_1CDCF0BAA79DF7B6 (meta_header_generator_id), INDEX IDX_1CDCF0BA1BC7E6B6 (writer_id), INDEX IDX_1CDCF0BA191F27CD (meta_corrector_id), INDEX IDX_1CDCF0BAC81CC188 (text_corrector_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro ADD CONSTRAINT FK_1CDCF0BAB254F930 FOREIGN KEY (structure_analyzer_id) REFERENCES tbl_neuro (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro ADD CONSTRAINT FK_1CDCF0BAA79DF7B6 FOREIGN KEY (meta_header_generator_id) REFERENCES tbl_neuro (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro ADD CONSTRAINT FK_1CDCF0BA1BC7E6B6 FOREIGN KEY (writer_id) REFERENCES tbl_neuro (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro ADD CONSTRAINT FK_1CDCF0BA191F27CD FOREIGN KEY (meta_corrector_id) REFERENCES tbl_neuro (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro ADD CONSTRAINT FK_1CDCF0BAC81CC188 FOREIGN KEY (text_corrector_id) REFERENCES tbl_neuro (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro DROP FOREIGN KEY FK_1CDCF0BAB254F930
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro DROP FOREIGN KEY FK_1CDCF0BAA79DF7B6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro DROP FOREIGN KEY FK_1CDCF0BA1BC7E6B6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro DROP FOREIGN KEY FK_1CDCF0BA191F27CD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tbl_scheme_neuro DROP FOREIGN KEY FK_1CDCF0BAC81CC188
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tbl_scheme_neuro
        SQL);
    }
}
