<?php

namespace App\Entity;

use App\Repository\DateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DateRepository::class)]
#[ORM\Table(name: 'dates')]
class Date
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\Column]
    private ?bool $distanciel = null;

    #[ORM\OneToOne(mappedBy: 'date', cascade: ['persist', 'remove'])]
    private ?Participation $participations = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isDistanciel(): ?bool
    {
        return $this->distanciel;
    }

    public function setDistanciel(bool $distanciel): static
    {
        $this->distanciel = $distanciel;

        return $this;
    }

    public function getParticipations(): ?Participation
    {
        return $this->participations;
    }

    public function setParticipations(Participation $participations): static
    {
        // set the owning side of the relation if necessary
        if ($participations->getDate() !== $this) {
            $participations->setDate($this);
        }

        $this->participations = $participations;

        return $this;
    }
}
