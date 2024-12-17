<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Slot;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Slot>
 */
final class SlotRepository extends EntityRepository
{

    public function findOneByDoctorAndStartDate(\App\Entity\Doctor $doctor, \DateTime $start): ?Slot
    {
        return $this->findOneBy(['doctor' => $doctor->getId(), 'start' => $start]);
    }
}
