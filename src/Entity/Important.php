<?php

namespace App\Entity;

use App\Repository\ImportantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportantRepository::class)]
class Important
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $value = null;


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
