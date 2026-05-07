<?php

namespace App\Entity;

use App\Repository\PermisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermisRepository::class)]
class Permis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TypePermisVehicule::class, inversedBy: 'permis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypePermisVehicule $typePermisVehicule = null;


    #[ORM\Column(length: 255)]
    private ?string $numero = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateValidite = null;

    #[ORM\ManyToOne(inversedBy: 'permis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $chauffeur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypePermisVehicule(): ?TypePermisVehicule
    {
        return $this->typePermisVehicule;
    }

    public function setTypePermisVehicule(?TypePermisVehicule $typePermisVehicule): static
    {
        $this->typePermisVehicule = $typePermisVehicule;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getDateValidite(): ?\DateTimeImmutable
    {
        return $this->dateValidite;
    }

    public function setDateValidite(\DateTimeImmutable $dateValidite): static
    {
        $this->dateValidite = $dateValidite;

        return $this;
    }

    public function getChauffeur(): ?Chauffeur
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?Chauffeur $chauffeur): static
    {
        $this->chauffeur = $chauffeur;

        return $this;
    }
}