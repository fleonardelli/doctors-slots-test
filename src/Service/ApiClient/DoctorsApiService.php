<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use App\Service\ApiClient\DTO\DoctorDto;
use App\Service\ApiClient\DTO\SlotDto;
use App\Service\Exception\ApiClientException;
use DateTimeImmutable;
use Generator;
use JsonException;
use Psr\Log\LoggerInterface;

final readonly class DoctorsApiService
{
    public function __construct(
        private LoggerInterface $logger,
        private DoctorsApiClient $apiClient
    ) {
    }

    /**
     * @return Generator<SlotDto>
     * @throws ApiClientException
     */
    public function fetchDoctorSlots(int $doctorId): Generator
    {
        $slotsJson = $this->apiClient->fetchDoctorSlots($doctorId);

        try {
            $slots = $this->decode($slotsJson);

            foreach ($slots as $slot) {
                if (!isset($slot['start'], $slot['end'])) {
                    $this->logger->error(
                        'Missing expected fields in slot data',
                        [
                            'slot' => $slot,
                            'doctorId' => $doctorId
                        ]
                    );
                    continue;
                }
                try {
                    yield new SlotDto(
                        start: new DateTimeImmutable($slot['start']),
                        end: new DateTimeImmutable($slot['end'])
                    );
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Error parsing slot dates',
                        [
                            'slotData' => $slot,
                            'error' => $e->getMessage(),
                            'doctorId' => $doctorId
                        ]
                    );
                }
            }
        } catch (JsonException $e) {
            $this->logger->error(
                'Error fetching slots for doctor',
                [
                    'json' => $slotsJson,
                    'error' => $e->getMessage(),
                    'doctorId' => $doctorId
                ]
            );
            throw ApiClientException::becauseSlotsFetchFailed($e);
        }
    }

    /**
     * @return Generator<DoctorDto>
     * @throws ApiClientException
     */
    public function fetchDoctors(): Generator
    {
        $doctorsJson = $this->apiClient->fetchDoctors();

        try {
            $doctors = $this->decode($doctorsJson);

            foreach ($doctors as $doctor) {
                if (!isset($doctor['id'], $doctor['name'])) {
                    $this->logger->error(
                        'Missing expected fields in doctor data',
                        [
                            'doctor' => $doctor
                        ]
                    );
                    continue;
                }
                yield new DoctorDto(id: $doctor['id'], name: $doctor['name']);
            }
        } catch (JsonException $e) {
            $this->logger->error(
                'Error fetching doctors',
                [
                    'json' => $doctorsJson,
                    'error' => $e->getMessage(),
                ]
            );
            throw new ApiClientException('Error fetching doctors');
        }
    }

    /**
     * @throws JsonException
     */
    private function decode(string $json): array
    {
        return json_decode(
            json: $json,
            associative: true,
            depth: 16,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
