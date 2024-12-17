<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DoctorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'doctors')]
#[ORM\Entity(repositoryClass: DoctorRepository::class)]
final class Doctor
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $error = false;

    /**
     * @var Collection<int, Slot>
     */
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Slot::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $slots;


    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->slots = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function markError(): void
    {
        $this->error = true;
    }

    public function clearError(): void
    {
        $this->error = false;
    }

    public function hasError(): bool
    {
        return $this->error;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    public function setError(bool $error): void
    {
        $this->error = $error;
        // not adding fluent return here because this is most likely not used on creation
    }

    public function getSlots(): Collection
    {
        return $this->slots;
    }

    public function setSlots(Collection $slots): self
    {
        $this->slots = $slots;

        return $this;
    }

    public function addSlot(Slot $slot): self
    {
        if (!$this->slots->contains($slot)) {
            $this->slots->add($slot);
            $slot->setDoctor($this);
        }

        return $this;
    }
}
