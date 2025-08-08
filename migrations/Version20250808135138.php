<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250808135138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tblProductData table matching make_database.sql structure';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tblProductData (intProductDataId INT UNSIGNED AUTO_INCREMENT NOT NULL, strProductName VARCHAR(50) NOT NULL, strProductDesc VARCHAR(255) NOT NULL, strProductCode VARCHAR(10) NOT NULL, dtmAdded DATETIME DEFAULT NULL, dtmDiscontinued DATETIME DEFAULT NULL, stmTimestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE INDEX UNIQ_2C11248662F10A58 (strProductCode), PRIMARY KEY(intProductDataId)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tblProductData');
    }
}
