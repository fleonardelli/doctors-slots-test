<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class ApiClientException extends \Exception
{
    public static function becauseSlotsFetchFailed(?\Throwable $previous = null): self
    {
        return new self(message: 'Failed to fetch slots', previous: $previous);
    }
}
