<?php

namespace App\Service;

use App\Repository\BookingRepository;

final class TrackingCodeGenerator
{
    public function __construct(private readonly BookingRepository $bookings)
    {
    }

    public function generate(): string
    {
        do {
            $code = 'YCR-'.strtoupper(bin2hex(random_bytes(5)));
        } while ($this->bookings->findOneBy(['trackingCode' => $code]) !== null);

        return $code;
    }
}
