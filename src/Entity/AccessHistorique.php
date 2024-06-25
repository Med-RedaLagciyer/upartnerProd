<?php

namespace App\Entity;

use App\Repository\AccessHistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessHistoriqueRepository::class)]
class AccessHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'accessHistoriques')]
    private ?User $User = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAccess = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): static
    {
        $this->User = $User;

        return $this;
    }

    public function getDateAccess(): ?\DateTimeInterface
    {
        return $this->dateAccess;
    }

    public function setDateAccess(?\DateTimeInterface $dateAccess): static
    {
        $this->dateAccess = $dateAccess;

        return $this;
    }
}
