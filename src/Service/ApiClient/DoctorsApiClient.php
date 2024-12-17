<?php

declare(strict_types=1);

namespace App\Service\ApiClient;

use App\Service\Exception\ApiClientException;
use Psr\Log\LoggerInterface;

final readonly class DoctorsApiClient
{
    private const string DOCTORS_ENDPOINT = '/api/doctors';
    private const string SLOTS_ENDPOINT = '/api/doctors/%s/slots';

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws ApiClientException
     */
    public function fetchDoctorSlots(int $doctorId): string
    {
        $url = sprintf(getenv('BASE_DOCTORS_API_URL') . self::SLOTS_ENDPOINT, $doctorId);
        return $this->fetchData($url);
    }

    /**
     * @throws ApiClientException
     */
    public function fetchDoctors(): string
    {
        $url = getenv('BASE_DOCTORS_API_URL') . self::DOCTORS_ENDPOINT;
        return $this->fetchData($url);
    }

    private function fetchData(string $url): string
    {
        $auth = base64_encode(
            sprintf(
                '%s:%s',
                getenv('DOCTORS_API_USER'),
                getenv('DOCTORS_API_PASSWORD'),
            ),
        );

        $response = @file_get_contents(
            filename: $url,
            context: stream_context_create(
                [
                    'http' => [
                        'header' => 'Authorization: Basic ' . $auth,
                    ],
                ],
            ),
        );

        if ($response === false) {
            $this->logger->error('Error fetching data from API', ['url' => $url]);
            throw new ApiClientException('Error fetching data from API');
        }

        return $response;
    }
}
