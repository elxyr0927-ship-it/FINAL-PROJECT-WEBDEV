<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
#[ORM\UniqueConstraint(name: 'UNIQ_BOOKING_TRACKING_CODE', fields: ['trackingCode'])]
#[ORM\Index(name: 'IDX_BOOKING_STATUS', columns: ['status'])]
#[ORM\Index(name: 'IDX_BOOKING_PAYMENT_STATUS', columns: ['payment_status'])]
#[ORM\Index(name: 'IDX_BOOKING_CREATED_AT', columns: ['created_at'])]
#[UniqueEntity(fields: ['trackingCode'])]
class Booking
{
    public const STATUSES = ['pending', 'dispatched', 'delivered', 'cancelled'];
    public const PAYMENT_STATUSES = ['unpaid', 'under_review', 'paid'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $trackingCode = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private ?string $customerName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $pickupRegion = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $deliveryRegion = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $pickupAddress = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $deliveryAddress = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $weight = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['pending', 'dispatched', 'delivered', 'cancelled'])]
    private string $status = 'pending';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['unpaid', 'under_review', 'paid'])]
    private string $paymentStatus = 'unpaid';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private ?string $totalCost = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, StatusHistory>
     */
    #[ORM\OneToMany(mappedBy: 'booking', targetEntity: StatusHistory::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $statusHistories;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statusHistories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(string $trackingCode): static
    {
        $this->trackingCode = $trackingCode;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $customerName): static
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getPickupRegion(): ?string
    {
        return $this->pickupRegion;
    }

    public function setPickupRegion(?string $pickupRegion): static
    {
        $this->pickupRegion = $pickupRegion;

        return $this;
    }

    public function getDeliveryRegion(): ?string
    {
        return $this->deliveryRegion;
    }

    public function setDeliveryRegion(?string $deliveryRegion): static
    {
        $this->deliveryRegion = $deliveryRegion;

        return $this;
    }

    public function getPickupAddress(): ?string
    {
        return $this->pickupAddress;
    }

    public function setPickupAddress(?string $pickupAddress): static
    {
        $this->pickupAddress = $pickupAddress;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public static function statusTransitionError(string $current, string $next, string $payment): ?string
    {
        if ($current === $next) {
            return null;
        }

        if (in_array($current, ['delivered', 'cancelled'], true)) {
            return sprintf('%s shipments are locked and their status cannot be changed.', ucfirst($current));
        }

        if ($next === 'pending') {
            return 'A shipment cannot be reverted to pending once it has left the pending stage.';
        }

        if ($next === 'delivered' && $current !== 'dispatched') {
            return 'A shipment must be dispatched before it can be marked as delivered.';
        }

        if (in_array($next, ['dispatched', 'delivered'], true) && $payment !== 'paid') {
            return 'Payment must be marked paid before dispatch or delivery.';
        }

        return null;
    }

    public static function paymentTransitionError(string $current, string $next, string $status): ?string
    {
        if ($current === $next) {
            return null;
        }

        if ($current === 'paid') {
            return 'Payment cannot be changed once it has been marked as paid.';
        }

        if (in_array($status, ['dispatched', 'delivered'], true) && $next !== 'paid') {
            return 'Dispatched or delivered shipments must remain marked as paid.';
        }

        return null;
    }

    /**
     * @return array<string, string> value => label
     */
    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pending',
            'dispatched' => 'Dispatched',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * @return array<string, string> value => label
     */
    public static function paymentStatusLabels(): array
    {
        return [
            'unpaid' => 'Unpaid',
            'under_review' => 'Under review',
            'paid' => 'Paid',
        ];
    }

    /**
     * @return array<string, string> label => value
     */
    public static function statusChoices(string $current, string $payment): array
    {
        $labels = self::statusLabels();

        $allowed = match ($current) {
            'delivered', 'cancelled' => [$current],
            'dispatched' => ['dispatched', 'delivered', 'cancelled'],
            'pending' => $payment === 'paid'
                ? ['pending', 'dispatched', 'cancelled']
                : ['pending', 'cancelled'],
            default => array_keys($labels),
        };

        $choices = [];
        foreach ($allowed as $value) {
            $choices[$labels[$value]] = $value;
        }

        return $choices;
    }

    /**
     * @return array<string, string> label => value
     */
    public static function paymentChoices(string $current, string $status): array
    {
        $labels = self::paymentStatusLabels();

        $allowed = match (true) {
            in_array($status, ['dispatched', 'delivered'], true) => ['paid'],
            $current === 'paid' => ['paid'],
            default => ['unpaid', 'under_review', 'paid'],
        };

        $choices = [];
        foreach ($allowed as $value) {
            $choices[$labels[$value]] = $value;
        }

        return $choices;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getTotalCost(): ?string
    {
        return $this->totalCost;
    }

    public function setTotalCost(string $totalCost): static
    {
        $this->totalCost = $totalCost;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, StatusHistory>
     */
    public function getStatusHistories(): Collection
    {
        return $this->statusHistories;
    }

    public function addStatusHistory(StatusHistory $statusHistory): static
    {
        if (!$this->statusHistories->contains($statusHistory)) {
            $this->statusHistories->add($statusHistory);
            $statusHistory->setBooking($this);
        }

        return $this;
    }

    public function removeStatusHistory(StatusHistory $statusHistory): static
    {
        $this->statusHistories->removeElement($statusHistory);

        return $this;
    }
}
