<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111163520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the YOCOR Express Admin and Staff schema.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bookings (id INT AUTO_INCREMENT NOT NULL, tracking_code VARCHAR(20) NOT NULL, customer_name VARCHAR(120) NOT NULL, customer_email VARCHAR(180) NOT NULL, pickup_region VARCHAR(50) NOT NULL, delivery_region VARCHAR(50) NOT NULL, pickup_address LONGTEXT NOT NULL, delivery_address LONGTEXT NOT NULL, weight NUMERIC(8, 2) NOT NULL, status VARCHAR(20) NOT NULL, payment_status VARCHAR(20) NOT NULL, total_cost NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, service_id INT NOT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_BOOKING_TRACKING_CODE (tracking_code), INDEX IDX_7A853C35ED5CA9E6 (service_id), INDEX IDX_7A853C35B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_messages (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_41278201A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE services (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, capacity INT NOT NULL, base_price NUMERIC(10, 2) NOT NULL, price_per_kg NUMERIC(10, 2) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE status_history (id INT AUTO_INCREMENT NOT NULL, old_status VARCHAR(20) NOT NULL, new_status VARCHAR(20) NOT NULL, checkpoint VARCHAR(150) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, booking_id INT NOT NULL, changed_by_id INT DEFAULT NULL, INDEX IDX_2F6A07CE3301C60 (booking_id), INDEX IDX_2F6A07CE828AD0A0 (changed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(120) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), UNIQUE INDEX UNIQ_USER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT FK_7A853C35ED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id)');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT FK_7A853C35B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE contact_messages ADD CONSTRAINT FK_41278201A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE status_history ADD CONSTRAINT FK_2F6A07CE3301C60 FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE status_history ADD CONSTRAINT FK_2F6A07CE828AD0A0 FOREIGN KEY (changed_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE status_history');
        $this->addSql('DROP TABLE contact_messages');
        $this->addSql('DROP TABLE bookings');
        $this->addSql('DROP TABLE services');
        $this->addSql('DROP TABLE user');
    }
}
