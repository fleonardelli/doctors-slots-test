<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Doctor;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Doctor>
 */
final class DoctorRepository extends EntityRepository
{
    public function save(Doctor $doctor, bool $flush = false): void
    {
        $this->getEntityManager()->persist($doctor);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
