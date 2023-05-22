<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    private ?User $userCreated = null;

    #[ORM\ManyToOne]
    private ?User $userUpdated = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated = null;

    #[ORM\ManyToOne(inversedBy: 'reponses')]
    private ?reclamation $reclamation = null;

    #[ORM\Column(nullable: true)]
    private ?bool $userSeen = null;

    #[ORM\Column(nullable: true)]
    private ?bool $admin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getUserCreated(): ?User
    {
        return $this->userCreated;
    }

    public function setUserCreated(?User $userCreated): self
    {
        $this->userCreated = $userCreated;

        return $this;
    }

    public function getUserUpdated(): ?User
    {
        return $this->userUpdated;
    }

    public function setUserUpdated(?User $userUpdated): self
    {
        $this->userUpdated = $userUpdated;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getReclamation(): ?reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?reclamation $reclamation): self
    {
        $this->reclamation = $reclamation;

        return $this;
    }

    public function isUserSeen(): ?bool
    {
        return $this->userSeen;
    }

    public function setUserSeen(?bool $userSeen): self
    {
        $this->userSeen = $userSeen;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(?bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }
}
