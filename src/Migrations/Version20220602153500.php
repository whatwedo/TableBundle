<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220602153500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'creates "whatwedo_table_filter" table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE whatwedo_table_filter (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, path VARCHAR(256) NOT NULL, arguments LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', conditions LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', description VARCHAR(256) DEFAULT NULL, INDEX IDX_AD53C190B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE whatwedo_table_filter');
    }
}
