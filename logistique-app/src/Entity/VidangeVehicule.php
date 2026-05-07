<?php

namespace App\Entity;

use App\Repository\VidangeVehiculeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: VidangeVehiculeRepository::class)]
class VidangeVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vidanges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AffectationVehicule $affectation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de vidange est obligatoire")]
    private ?\DateTimeInterface $dateVidange = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le kilométrage est obligatoire")]
    #[Assert\Positive(message: "Le kilométrage doit être positif")]
    private ?int $kilometrageVidange = null;

    // Nouveaux champs recommandés
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeHuile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceFiltre = null;

    #[ORM\Column(nullable: true)]
    private ?float $quantiteHuile = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $cout = null;

    #[ORM\ManyToOne(targetEntity: PrestataireIntervention::class, inversedBy: 'vidanges')]
    #[ORM\JoinColumn(name: 'prestataire_id', referencedColumnName: 'id')]
    private ?PrestataireIntervention $prestataire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $prochaineVidangePrevue = null;

    #[ORM\Column(nullable: true)]
    private ?int $kilometrageProchaineVidange = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column]
    private ?bool $effectuee = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\ManyToOne(inversedBy: 'vidangesValidees')]
    private ?User $validePar = null;

    // Getters et setters existants
    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateVidange(): ?\DateTimeInterface
    {
        return $this->dateVidange;
    }

    public function setDateVidange(\DateTimeInterface $dateVidange): static
    {
        $this->dateVidange = $dateVidange;
        return $this;
    }

    public function getKilometrageVidange(): ?int
    {
        return $this->kilometrageVidange;
    }

    public function setKilometrageVidange(int $kilometrageVidange): static
    {
        $this->kilometrageVidange = $kilometrageVidange;
        return $this;
    }

    // Getters et setters pour les nouveaux champs
    public function getTypeHuile(): ?string
    {
        return $this->typeHuile;
    }

    public function setTypeHuile(?string $typeHuile): static
    {
        $this->typeHuile = $typeHuile;
        return $this;
    }

    public function getReferenceFiltre(): ?string
    {
        return $this->referenceFiltre;
    }

    public function setReferenceFiltre(?string $referenceFiltre): static
    {
        $this->referenceFiltre = $referenceFiltre;
        return $this;
    }

    public function getQuantiteHuile(): ?float
    {
        return $this->quantiteHuile;
    }

    public function setQuantiteHuile(?float $quantiteHuile): static
    {
        $this->quantiteHuile = $quantiteHuile;
        return $this;
    }

    public function getCout(): ?string
    {
        return $this->cout;
    }

    public function setCout(?string $cout): static
    {
        $this->cout = $cout;
        return $this;
    }

    public function getPrestataire(): ?PrestataireIntervention
    {
        return $this->prestataire;
    }

    public function setPrestataire(?PrestataireIntervention $prestataire): static
    {
        $this->prestataire = $prestataire;
        return $this;
    }

    public function getProchaineVidangePrevue(): ?\DateTimeInterface
    {
        return $this->prochaineVidangePrevue;
    }

    public function setProchaineVidangePrevue(?\DateTimeInterface $prochaineVidangePrevue): static
    {
        $this->prochaineVidangePrevue = $prochaineVidangePrevue;
        return $this;
    }

    public function getKilometrageProchaineVidange(): ?int
    {
        return $this->kilometrageProchaineVidange;
    }

    public function setKilometrageProchaineVidange(?int $kilometrageProchaineVidange): static
    {
        $this->kilometrageProchaineVidange = $kilometrageProchaineVidange;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;
        return $this;
    }

    public function isEffectuee(): ?bool
    {
        return $this->effectuee;
    }

    public function setEffectuee(bool $effectuee): static
    {
        $this->effectuee = $effectuee;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getValidePar(): ?User
    {
        return $this->validePar;
    }

    public function setValidePar(?User $validePar): static
    {
        $this->validePar = $validePar;
        return $this;
    }
}
