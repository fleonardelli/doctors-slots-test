<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Service\Logger\DoctorsLogger;
use App\Service\Logger\MonToSatHandler;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Console\Application;
use Doctrine\ORM\EntityManager;
use App\Command\SynchronizeDoctorSlotsCommand;
use Symfony\Component\Dotenv\Dotenv;

// Create EntityManager
$paths = [__DIR__ . '/src/Entity'];
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: $paths,
    isDevMode: true
);
$conn = DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'memory' => true,
]);
$entityManager = new EntityManager($conn, $config);

// Create the Logger
$monToSatHandler = new MonToSatHandler('php://stderr');
$logger = new DoctorsLogger('doctors', [$monToSatHandler]);

// Load .env file
$dotenv = new Dotenv();
$dotenv->usePutenv()->bootEnv(__DIR__.'/.env');

// Create the console application and add commands
$application = new Application();
$application->add(new SynchronizeDoctorSlotsCommand($entityManager, $logger));

// Run the app
$application->run();
