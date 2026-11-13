<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
#[ORM\UniqueConstraint(name: 'UNIQ_SERVICE_NAME', fields: ['name'])]
#[ORM\Index(name: 'IDX_SERVICE_IS_ACTIVE', columns: ['is_active'])]
#[UniqueEntity(fields: ['name'], message: 'A service with this name already exists.')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $basePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $pricePerKg = null;

    #[ORM\Column]
    private bool $isActive = true;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getBasePrice(): ?string
    {
        return $this->basePrice;
    }

    public function setBasePrice(?string $basePrice): static
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    public function getPricePerKg(): ?string
    {
        return $this->pricePerKg;
    }

    public function setPricePerKg(?string $pricePerKg): static
    {
        $this->pricePerKg = $pricePerKg;

        return $this;
    }

    public function calculateTotalCost(string $weight): string
    {
        if ($this->basePrice === null || $this->pricePerKg === null) {
            throw new \LogicException('A service must have prices before calculating a shipment cost.');
        }

        $extraWeightHundredths = max(0, self::toCents($weight) - 100);
        $weightCostCents = intdiv($extraWeightHundredths * self::toCents($this->pricePerKg) + 50, 100);
        $totalCents = self::toCents($this->basePrice) + $weightCostCents;

        return sprintf('%d.%02d', intdiv($totalCents, 100), $totalCents % 100);
    }

    private static function toCents(string $amount): int
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $amount)) {
            throw new \InvalidArgumentException('Prices and weights must be non-negative decimals with at most two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
