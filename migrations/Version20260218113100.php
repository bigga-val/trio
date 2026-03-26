<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Link existing service messages to their corresponding clients
 */
final class Version20260218113100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link service messages to their corresponding clients based on nomAuteur';
    }

    public function up(Schema $schema): void
    {
        // Pour chaque message du service client (is_from_client = false) sans client associé,
        // on va chercher le client qui a le même nom
        $this->addSql('
            UPDATE message m
            SET m.client_id = (
                SELECT c.id FROM client c 
                WHERE (c.nom_client = m.nom_auteur OR c.adresse_email = m.nom_auteur)
                LIMIT 1
            )
            WHERE m.is_from_client = false 
            AND m.client_id IS NULL
            AND m.nom_auteur IS NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        // Revert the changes
        $this->addSql('UPDATE message SET client_id = NULL WHERE is_from_client = false');
    }
}
