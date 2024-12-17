<?php

declare(strict_types=1);

namespace App\Service\DoctorSlotsSynchronizer;

interface DoctorSlotsSynchronizerInterface
{
    public function synchronizeDoctorSlots(): void;
}
