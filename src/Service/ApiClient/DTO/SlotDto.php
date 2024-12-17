<?php

declare(strict_types=1);

namespace App\Service\ApiClient\DTO;

use DateTimeImmutable;

final readonly class SlotDto
{
    public function __construct(
        private DateTimeImmutable $start,
        private DateTimeImmutable $end
    ) {
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): DateTimeImmutable
    {
        return $this->end;
    }
}
