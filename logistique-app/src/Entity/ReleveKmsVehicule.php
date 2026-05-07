<?php

namespace App\Entity;

use App\Repository\ReleveKmsVehiculeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReleveKmsVehiculeRepository::class)]
class ReleveKmsVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateReleve = null;

    #[ORM\Column]
    private ?int $kilometrage = null;

    #[ORM\ManyToOne(inversedBy: 'releveKmsVehicules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AffectationVehicule $affectation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReleve(): ?\DateTimeInterface
    {
        return $this->dateReleve;
    }

    public function setDateReleve(\DateTimeInterface $dateReleve): static
    {
        $this->dateReleve = $dateReleve;

        return $this;
    }

    public function getKilometrage(): ?int
    {
        return $this->kilometrage;
    }

    public function setKilometrage(int $kilometrage): static
    {
        $this->kilometrage = $kilometrage;

        return $this;
    }

    public function getAffectation(): ?AffectationVehicule
    {
        return $this->affectation;
    }

    public function setAffectation(?AffectationVehicule $affectation): static
    {
        $this->affectation = $affectation;

        return $this;
    }
}