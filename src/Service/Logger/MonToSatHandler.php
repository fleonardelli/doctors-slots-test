<?php

declare(strict_types=1);

namespace App\Service\Logger;

use DateTime;
use Monolog\Handler\StreamHandler;

final class MonToSatHandler extends StreamHandler
{
    protected function write(array $record): void
    {
        if ((new DateTime())->format('D') !== 'Sun') {
            parent::write($record);
        }
    }
}
