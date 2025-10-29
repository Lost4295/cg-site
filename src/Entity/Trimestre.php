<?php

namespace App\Entity;

use App\Repository\TrimestreRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrimestreRepository::class)]
class Trimestre
{
    #[ORM\Id]
    #[ORM\Column]
    private string $niveau;

    #[ORM\Id]
    #[ORM\Column]
    private int $trimestre;


    #[ORM\Column]
    private DateTime $date_debut;

    #[ORM\Column]
    private DateTime $date_fin;


    public function getId(): ?int
    {
        return $this->niveau." - T".$this->trimestre;
    }

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): Trimestre
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getTrimestre(): int
    {
        return $this->trimestre;
    }

    public function setTrimestre(int $trimestre): Trimestre
    {
        $this->trimestre = $trimestre;
        return $this;
    }

    public function getDateDebut(): DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(DateTime $date_debut): Trimestre
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): DateTime
    {
        return $this->date_fin;
    }

    public function setDateFin(DateTime $date_fin): Trimestre
    {
        $this->date_fin = $date_fin;
        return $this;
    }

}
