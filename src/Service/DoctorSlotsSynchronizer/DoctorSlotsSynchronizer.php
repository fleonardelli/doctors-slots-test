<?php
declare(strict_types=1);

namespace App\Service\DoctorSlotsSynchronizer;

use App\Entity\Doctor;
use App\Entity\Slot;
use App\Repository\DoctorRepository;
use App\Repository\SlotRepository;
use App\Service\ApiClient\DoctorsApiService;
use App\Service\ApiClient\DTO\SlotDto;
use App\Service\Exception\ApiClientException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class DoctorSlotsSynchronizer implements DoctorSlotsSynchronizerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DoctorRepository $doctorRepository,
        private readonly SlotRepository $slotsRepository,
        private readonly DoctorsApiService $doctorsApiService
    ) {
    }

    public function synchronizeDoctorSlots(): void
    {
        $doctors = $this->doctorsApiService->fetchDoctors();

        foreach ($doctors as $doctorDto) {
            $name = $this->normalizeName($doctorDto->getName());

            $doctor = $this->doctorRepository->find($doctorDto->getId())
                ?? new Doctor(id: (string)$doctorDto->getId(), name: $name);
            $doctor->setName($name);
            $doctor->clearError();

            try {
                $slotDtos = $this->doctorsApiService->fetchDoctorSlots($doctorDto->getId());

                // A transaction could start here, if we want all or nothing slots.
                foreach ($slotDtos as $slotDto) {
                    $start = DateTime::createFromImmutable($slotDto->getStart());
                    $end = DateTime::createFromImmutable($slotDto->getEnd());

                    $slot = $this->slotsRepository->findOneByDoctorAndStartDate(doctor: $doctor, start: $start)
                        ?: $this->createSlot(doctor: $doctor, slotDto: $slotDto);

                    if ($slot->isStale()) {
                        $slot->setEnd($end);
                    }

                    $doctor->addSlot($slot);
                }
            } catch (ApiClientException) {
                $doctor->markError();
            }

            $this->doctorRepository->save($doctor); // Slots will be saved as cascade persist is set in the entity.
        }
        $this->entityManager->flush(); // flush after saving, for better performance.
    }

    /**
     * This could be in a normalizer class
     */
    protected function normalizeName(string $fullName): string
    {
        [, $surname] = explode(' ', $fullName);

        /** @see https://www.youtube.com/watch?v=PUhU3qCf0Nk */
        if (0 === stripos($surname, "o'")) {
            return ucwords($fullName, ' \'');
        }

        return ucwords($fullName);
    }

    /**
     * This should be in a Factory if we want to be really picky about responsibilities.
     * Sth like SlotFactory::FromDoctorAndSlotDto()
     */
    protected function createSlot(Doctor $doctor, SlotDto $slotDto): Slot
    {
        $start = DateTime::createFromImmutable($slotDto->getStart());
        $end = DateTime::createFromImmutable($slotDto->getEnd());

        return new Slot(
            doctor: $doctor,
            start: $start,
            end: $end,
        );
    }
}
