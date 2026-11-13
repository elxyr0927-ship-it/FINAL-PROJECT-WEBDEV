<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add query indexes for status, payment, timestamps, and unique service names.
 */
final class Version20261112163812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for shipment filters, contact queries, status history, and unique service names.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_BOOKING_STATUS ON bookings (status)');
        $this->addSql('CREATE INDEX IDX_BOOKING_PAYMENT_STATUS ON bookings (payment_status)');
        $this->addSql('CREATE INDEX IDX_BOOKING_CREATED_AT ON bookings (created_at)');
        $this->addSql('CREATE INDEX IDX_CONTACT_IS_READ ON contact_messages (is_read)');
        $this->addSql('CREATE INDEX IDX_CONTACT_DELETED_AT ON contact_messages (deleted_at)');
        $this->addSql('CREATE INDEX IDX_CONTACT_CREATED_AT ON contact_messages (created_at)');
        $this->addSql('CREATE INDEX IDX_SERVICE_IS_ACTIVE ON services (is_active)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SERVICE_NAME ON services (name)');
        $this->addSql('CREATE INDEX IDX_STATUS_HISTORY_BOOKING_CREATED ON status_history (booking_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_BOOKING_STATUS ON bookings');
        $this->addSql('DROP INDEX IDX_BOOKING_PAYMENT_STATUS ON bookings');
        $this->addSql('DROP INDEX IDX_BOOKING_CREATED_AT ON bookings');
        $this->addSql('DROP INDEX IDX_CONTACT_IS_READ ON contact_messages');
        $this->addSql('DROP INDEX IDX_CONTACT_DELETED_AT ON contact_messages');
        $this->addSql('DROP INDEX IDX_CONTACT_CREATED_AT ON contact_messages');
        $this->addSql('DROP INDEX IDX_SERVICE_IS_ACTIVE ON services');
        $this->addSql('DROP INDEX UNIQ_SERVICE_NAME ON services');
        $this->addSql('DROP INDEX IDX_STATUS_HISTORY_BOOKING_CREATED ON status_history');
    }
}
