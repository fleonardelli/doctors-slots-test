<?php

declare(strict_types=1);

namespace Test\Unit\Service\ApiClient;

use App\Service\ApiClient\DoctorsApiClient;
use App\Service\ApiClient\DoctorsApiService;
use App\Service\ApiClient\DTO\DoctorDto;
use App\Service\ApiClient\DTO\SlotDto;
use App\Service\Exception\ApiClientException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;

final class DoctorsApiServiceTest extends TestCase
{
    private DoctorsApiService $apiService;
    private DoctorsApiClient $apiClient;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->apiClient = $this->createMock(DoctorsApiClient::class);
        $this->apiService = new DoctorsApiService($this->logger, $this->apiClient);
    }

    public function testShouldReturnSlotDtos(): void
    {
        $doctorId = 1;
        $slotsJson = '[{"start":"2023-07-01T10:00:00+00:00","end":"2023-07-01T11:00:00+00:00"}]';

        $this->apiClient
            ->method('fetchDoctorSlots')
            ->with($doctorId)
            ->willReturn($slotsJson);

        $slots = iterator_to_array($this->apiService->fetchDoctorSlots($doctorId));

        $this->assertCount(1, $slots);
        $this->assertInstanceOf(SlotDto::class, $slots[0]);
        $this->assertEquals('2023-07-01T10:00:00+00:00', $slots[0]->getStart()->format(DateTimeImmutable::ATOM));
        $this->assertEquals('2023-07-01T11:00:00+00:00', $slots[0]->getEnd()->format(DateTimeImmutable::ATOM));
    }

    public function testShouldLogErrorAndThrowExceptionOnJsonDecodeErrorWhenFetchingDoctorSlots(): void
    {
        $doctorId = 1;
        $invalidJson = '{invalid json}';

        $this->apiClient
            ->method('fetchDoctorSlots')
            ->with($doctorId)
            ->willReturn($invalidJson);

        $this->logger->expects($this->once())
            ->method('error');

        $this->expectException(ApiClientException::class);

        iterator_to_array($this->apiService->fetchDoctorSlots($doctorId));
    }

    public function testShouldLogErrorForMissingFieldsWhenFetchingDoctorSlots(): void
    {
        $doctorId = 1;
        $slotsJson = '[{"start":"2023-07-01T10:00:00+00:00"}]';

        $this->apiClient
            ->method('fetchDoctorSlots')
            ->with($doctorId)
            ->willReturn($slotsJson);

        $this->logger->expects($this->once())
            ->method('error');

        $slots = iterator_to_array($this->apiService->fetchDoctorSlots($doctorId));

        $this->assertCount(0, $slots);
    }

    public function testShouldReturnDoctorDtos(): void
    {
        $doctorsJson = '[{"id":1,"name":"John Doe"}]';

        $this->apiClient
            ->method('fetchDoctors')
            ->willReturn($doctorsJson);

        $doctors = iterator_to_array($this->apiService->fetchDoctors());

        $this->assertCount(1, $doctors);
        $this->assertInstanceOf(DoctorDto::class, $doctors[0]);
        $this->assertEquals(1, $doctors[0]->getId());
        $this->assertEquals('John Doe', $doctors[0]->getName());
    }

    public function testShouldLogErrorAndThrowExceptionOnJsonDecodeErrorWhenFetchingDoctors(): void
    {
        $invalidJson = '{invalid json}';

        $this->apiClient
            ->method('fetchDoctors')
            ->willReturn($invalidJson);

        $this->logger->expects($this->once())
            ->method('error');

        $this->expectException(ApiClientException::class);

        iterator_to_array($this->apiService->fetchDoctors());
    }

    public function testFetchDoctorsShouldLogErrorForMissingFieldsWhenFetchingDoctors(): void
    {
        $doctorsJson = '[{"id":1}]';

        $this->apiClient
            ->method('fetchDoctors')
            ->willReturn($doctorsJson);

        $this->logger->expects($this->once())
            ->method('error');

        $doctors = iterator_to_array($this->apiService->fetchDoctors());

        $this->assertCount(0, $doctors);
    }

    public function testShouldLogErrorWhenMalformedSlotDatesFromApi(): void
    {
        $doctorId = 1;
        $slotsJson = '[{"start":"2023-07-01T10:00:00+00:00","end":"2023-07-01T11:00:00+00:00"},{"start":"2023-07-02T10:00:00+00:00","end":"2023-07-02T11:00:00+00:00"},{"start":"invalid-date","end":"2023-07-03T11:00:00+00:00"}]';

        $this->apiClient
            ->method('fetchDoctorSlots')
            ->with($doctorId)
            ->willReturn($slotsJson);

        $this->logger->expects($this->once())
            ->method('error');

        $slots = iterator_to_array($this->apiService->fetchDoctorSlots($doctorId));

        $this->assertCount(2, $slots);
    }
}
