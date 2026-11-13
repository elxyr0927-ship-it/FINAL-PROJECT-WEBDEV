<?php

namespace App\Entity;

use App\Repository\StatusHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StatusHistoryRepository::class)]
#[ORM\Table(name: 'status_history')]
#[ORM\Index(name: 'IDX_STATUS_HISTORY_BOOKING_CREATED', columns: ['booking_id', 'created_at'])]
class StatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'statusHistories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Booking $booking = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: Booking::STATUSES)]
    private string $oldStatus = 'pending';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: Booking::STATUSES)]
    private string $newStatus = 'pending';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $changedBy = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $checkpoint = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function getCheckpoint(): ?string
    {
        return $this->checkpoint;
    }

    public function setCheckpoint(?string $checkpoint): static
    {
        $this->checkpoint = $checkpoint;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setBooking(Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setStatuses(string $oldStatus, string $newStatus): static
    {
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;

        return $this;
    }

    public function setChangedBy(?User $changedBy): static
    {
        $this->changedBy = $changedBy;

        return $this;
    }
}
