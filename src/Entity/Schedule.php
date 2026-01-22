<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    paginationEnabled: false // <--- ДОБАВИ ТОЗИ РЕД
)]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $date = null;

    #[ORM\Column(length: 255)]
    private ?string $userId = null; // "husband" or "wife"

    #[ORM\Column(length: 255)]
    private ?string $type = null;   // Стринг, за да пази enum индекса

    #[ORM\Column]
    private ?bool $isOnCall = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function isIsOnCall(): ?bool
    {
        return $this->isOnCall;
    }

    public function setIsOnCall(bool $isOnCall): static
    {
        $this->isOnCall = $isOnCall;
        return $this;
    }
}