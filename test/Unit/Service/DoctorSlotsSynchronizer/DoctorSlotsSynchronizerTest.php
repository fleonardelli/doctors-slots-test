<?php

declare(strict_types=1);

namespace Test\Unit\Service\DoctorSlotsSynchronizer;

use App\Entity\Doctor;
use App\Entity\Slot;
use App\Repository\DoctorRepository;
use App\Repository\SlotRepository;
use App\Service\ApiClient\DoctorsApiService;
use App\Service\ApiClient\DTO\DoctorDto;
use App\Service\ApiClient\DTO\SlotDto;
use App\Service\Exception\ApiClientException;
use App\Service\DoctorSlotsSynchronizer\DoctorSlotsSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

final class DoctorSlotsSynchronizerTest extends TestCase
{
    private DoctorSlotsSynchronizer $synchronizer;
    private EntityManagerInterface $entityManager;
    private DoctorRepository $doctorRepository;
    private SlotRepository $slotRepository;
    private DoctorsApiService $apiService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctorRepository = $this->createMock(DoctorRepository::class);
        $this->slotRepository = $this->createMock(SlotRepository::class);
        $this->apiService = $this->createMock(DoctorsApiService::class);

        $this->synchronizer = new DoctorSlotsSynchronizer(
            entityManager: $this->entityManager,
            doctorRepository: $this->doctorRepository,
            slotsRepository: $this->slotRepository,
            doctorsApiService: $this->apiService
        );
    }

    private function createGenerator(array $items): \Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }

    public function testShouldFetchAndSaveDoctorsAndSlots(): void
    {
        $doctorDto = new DoctorDto(1, 'John Doe');
        $slotDto1 = new SlotDto(new DateTimeImmutable('2023-07-01 10:00:00'), new DateTimeImmutable('2023-07-01 11:00:00'));
        $slotDto2 = new SlotDto(new DateTimeImmutable('2023-07-02 10:00:00'), new DateTimeImmutable('2023-07-02 11:00:00'));

        $this->apiService
            ->method('fetchDoctors')
            ->willReturn($this->createGenerator([$doctorDto]));

        $this->doctorRepository
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->apiService
            ->method('fetchDoctorSlots')
            ->with(1)
            ->willReturn($this->createGenerator([$slotDto1, $slotDto2]));

        $this->slotRepository
            ->method('findOneByDoctorAndStartDate')
            ->willReturnOnConsecutiveCalls(null, null);

        $this->doctorRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($doctor) {
                return $doctor instanceof Doctor && $doctor->getName() === 'John Doe';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->synchronizer->synchronizeDoctorSlots();
    }

    public function testShouldHandleApiClientException(): void
    {
        $doctorDto = new DoctorDto(1, 'John Doe');

        $this->apiService
            ->method('fetchDoctors')
            ->willReturn($this->createGenerator([$doctorDto]));

        $this->doctorRepository
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->apiService
            ->method('fetchDoctorSlots')
            ->with(1)
            ->willThrowException(new ApiClientException());

        $this->doctorRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($doctor) {
                // Assert when the API throws an exception, the doctor is marked with an error
                return $doctor instanceof Doctor && $doctor->hasError();
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->synchronizer->synchronizeDoctorSlots();
    }

    public function testShouldUpdateExistingDoctorAndAddNewSlot(): void
    {
        $doctorDto = new DoctorDto(1, 'John Doe');
        $newSlotDto = new SlotDto(new DateTimeImmutable('2023-07-03 10:00:00'), new DateTimeImmutable('2023-07-03 11:00:00'));

        $existingDoctor = new Doctor('1', 'John Doe');
        $existingSlot = new Slot($existingDoctor, new \DateTime('2023-07-01 10:00:00'), new \DateTime('2023-07-01 11:00:00'));

        $existingDoctor->addSlot($existingSlot);

        $this->apiService
            ->method('fetchDoctors')
            ->willReturn($this->createGenerator([$doctorDto]));

        $this->doctorRepository
            ->method('find')
            ->with(1)
            ->willReturn($existingDoctor);

        $this->apiService
            ->method('fetchDoctorSlots')
            ->with(1)
            ->willReturn($this->createGenerator([$newSlotDto]));

        $this->slotRepository
            ->method('findOneByDoctorAndStartDate')
            ->with($existingDoctor, new \DateTime('2023-07-03 10:00:00'))
            ->willReturn(null);  // Simulate that this is a new slot

        $this->doctorRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($doctor) {
                return $doctor instanceof Doctor && $doctor->getName() === 'John Doe';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->synchronizer->synchronizeDoctorSlots();

        // Assert that the new slot was added to the existing doctor
        $this->assertCount(2, $existingDoctor->getSlots());
        $this->assertEquals(new \DateTime('2023-07-03 10:00:00'), $existingDoctor->getSlots()[1]->getStart());
        $this->assertEquals(new \DateTime('2023-07-03 11:00:00'), $existingDoctor->getSlots()[1]->getEnd());
    }
}
