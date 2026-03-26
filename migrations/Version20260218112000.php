<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

/**
 * Hash all existing client passwords that are not yet hashed
 */
final class Version20260218112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hash all existing client passwords';
    }

    public function up(Schema $schema): void
    {
        // Récupérer tous les clients
        $clientsData = $this->connection->fetchAllAssociative('SELECT id, password FROM client WHERE LENGTH(password) < 60');
        
        $hasher = new NativePasswordHasher();
        
        foreach ($clientsData as $client) {
            $hashedPassword = $hasher->hash($client['password']);
            $this->addSql('UPDATE client SET password = ? WHERE id = ?', [
                $hashedPassword,
                $client['id']
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // Cannot reverse password hashing
    }
}
