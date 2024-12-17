<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\SlotRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: SlotRepository::class)]
#[ORM\Table(name: 'slots')]
#[HasLifecycleCallbacks]
final class Slot
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private string $id;

    #[ORM\ManyToOne(inversedBy: 'slots')]
    #[ORM\JoinColumn(nullable: false)]
    private Doctor $doctor;

    #[ORM\Column(type: 'datetime')]
    private DateTime $start;

    #[ORM\Column(type: 'datetime')]
    private DateTime $end;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $createdAt;

    public function __construct(Doctor $doctor, DateTime $start, DateTime $end)
    {
        $this->doctor = $doctor;
        $this->start = $start;
        $this->end = $end;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getStart(): DateTime
    {
        return $this->start;
    }

    public function setEnd(DateTime $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function isStale(): bool
    {
        return $this->createdAt < new DateTime('5 minutes ago');
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDoctor(): Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(Doctor $doctor): void
    {
        $this->doctor = $doctor;
    }

    public function getEnd(): DateTime
    {
        return $this->end;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
