<?php

namespace App\Tests\Entity;

use App\Entity\Service;
use PHPUnit\Framework\TestCase;

final class ServicePricingTest extends TestCase
{
    public function testBasePriceCoversFirstKilogramAndAdditionalWeightUsesPerKgRate(): void
    {
        $service = (new Service())
            ->setBasePrice('100.00')
            ->setPricePerKg('30.00');

        self::assertSame('100.00', $service->calculateTotalCost('1.00'));
        self::assertSame('100.00', $service->calculateTotalCost('0.50'));
        self::assertSame('115.00', $service->calculateTotalCost('1.50'));
        self::assertSame('130.00', $service->calculateTotalCost('2.00'));
    }

    public function testWeightChargeRoundsToTheNearestCent(): void
    {
        $service = (new Service())
            ->setBasePrice('100.00')
            ->setPricePerKg('0.01');

        self::assertSame('100.01', $service->calculateTotalCost('1.50'));
    }
}
