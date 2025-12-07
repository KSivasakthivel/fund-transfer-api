<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231201000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accounts and transactions tables';
    }

    public function up(Schema $schema): void
    {
        // Create accounts table
        $this->addSql('CREATE TABLE accounts (
            id INT AUTO_INCREMENT NOT NULL,
            account_number VARCHAR(20) NOT NULL,
            holder_name VARCHAR(255) NOT NULL,
            balance NUMERIC(15, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            version INT DEFAULT 0 NOT NULL,
            UNIQUE INDEX UNIQ_CAC89EACB0E74C00 (account_number),
            INDEX idx_account_number (account_number),
            INDEX idx_status (status),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create transactions table
        $this->addSql('CREATE TABLE transactions (
            id INT AUTO_INCREMENT NOT NULL,
            source_account_id INT NOT NULL,
            destination_account_id INT NOT NULL,
            reference_number VARCHAR(50) NOT NULL,
            amount NUMERIC(15, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            description VARCHAR(500) DEFAULT NULL,
            failure_reason TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            metadata JSON NOT NULL,
            UNIQUE INDEX UNIQ_EAA81A4C1E6FEAA9 (reference_number),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            INDEX idx_reference_number (reference_number),
            INDEX IDX_EAA81A4C3ADFE372 (source_account_id),
            INDEX IDX_EAA81A4CE94D747A (destination_account_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign keys
        $this->addSql('ALTER TABLE transactions 
            ADD CONSTRAINT FK_EAA81A4C3ADFE372 
            FOREIGN KEY (source_account_id) 
            REFERENCES accounts (id)');
            
        $this->addSql('ALTER TABLE transactions 
            ADD CONSTRAINT FK_EAA81A4CE94D747A 
            FOREIGN KEY (destination_account_id) 
            REFERENCES accounts (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C3ADFE372');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CE94D747A');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE accounts');
    }
}
