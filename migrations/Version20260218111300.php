<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Mark all existing messages from service clients as read
 */
final class Version20260218111300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mark all existing service messages as read';
    }

    public function up(Schema $schema): void
    {
        // Mark all non-client messages (service messages) as read
        $this->addSql('UPDATE message SET is_read = true WHERE is_from_client = false');
    }

    public function down(Schema $schema): void
    {
        // Revert to false
        $this->addSql('UPDATE message SET is_read = false WHERE is_from_client = false');
    }
}
