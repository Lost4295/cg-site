<?php

namespace App\Entity;

use App\Repository\DailyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: DailyRepository::class)]
class Daily
{

    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $user_id = null;

    #[ORM\Id]
    #[ORM\Column]
    private ?string $date = null;

    #[ORM\Id]
    #[ORM\Column(length: 4)]
    private ?string $location = null;

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function setUserId(string $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function setUser(UserInterface $user)
    {
        $this->user_id = $user->getUserIdentifier();
    }
}
