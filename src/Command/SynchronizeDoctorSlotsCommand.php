<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Doctor;
use App\Entity\Slot;
use App\Repository\DoctorRepository;
use App\Repository\SlotRepository;
use App\Service\ApiClient\DoctorsApiClient;
use App\Service\ApiClient\DoctorsApiService;
use App\Service\DoctorSlotsSynchronizer\DoctorSlotsSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SynchronizeDoctorSlotsCommand extends Command
{
    protected static $defaultName = 'app:synchronize-doctor-slots';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronizes doctor slots.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $synchronizer = new DoctorSlotsSynchronizer(
            $this->entityManager,
            new DoctorRepository($this->entityManager, new ClassMetadata(Doctor::class)),
            new SlotRepository($this->entityManager, new ClassMetadata(Slot::class)),
            new DoctorsApiService($this->logger, new DoctorsApiClient($this->logger))
        );
        $synchronizer->synchronizeDoctorSlots();

        $output->writeln('Doctor slots synchronized successfully.');

        return Command::SUCCESS;
    }
}
