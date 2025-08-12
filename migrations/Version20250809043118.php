<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250809043118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor ProductData to modern naming conventions and add stock_level/price fields';
    }

    public function up(Schema $schema): void
    {
        // Rename table
        $this->addSql('RENAME TABLE tblProductData TO products');
        
        // Add new columns first
        $this->addSql('ALTER TABLE products ADD COLUMN stock_level INT DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD COLUMN price DECIMAL(10,2) DEFAULT NULL');
        
        // Rename columns
        $this->addSql('ALTER TABLE products CHANGE intProductDataId id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE strProductName name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE strProductDesc description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE strProductCode code VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE dtmAdded added_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE products CHANGE dtmDiscontinued discontinued_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE products CHANGE stmTimestamp updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rename columns back
        $this->addSql('ALTER TABLE products CHANGE id intProductDataId INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE name strProductName VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE description strProductDesc VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE code strProductCode VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE products CHANGE added_at dtmAdded DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE products CHANGE discontinued_at dtmDiscontinued DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE products CHANGE updated_at stmTimestamp DATETIME NOT NULL');
        
        // Remove new columns
        $this->addSql('ALTER TABLE products DROP COLUMN stock_level');
        $this->addSql('ALTER TABLE products DROP COLUMN price');
        
        // Rename table back
        $this->addSql('RENAME TABLE products TO tblProductData');
    }
}
