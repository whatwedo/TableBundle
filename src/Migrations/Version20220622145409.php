<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220622145409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'creates "whatwedo_table_filter" table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE whatwedo_table_filter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, path VARCHAR(256) NOT NULL, arguments LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', conditions LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', description VARCHAR(256) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE whatwedo_table_filter');
    }
}
