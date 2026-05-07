<?php

namespace App\Entity;

use App\Repository\DocumentVehiculeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentVehiculeRepository::class)]
#[ORM\Table(name: 'dossier_vehicule_document')]
#[ORM\UniqueConstraint(name: 'unique_active_document', columns: ['id_document', 'id_vehicule','is_active'])]
class DocumentVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_dossier_vehicule_document')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TypeDocument::class, inversedBy:'documents')]
    #[ORM\JoinColumn(name: 'id_document', referencedColumnName: 'id_document')]
    private ?TypeDocument $typeDocument = null;

    #[ORM\Column(name: 'date_expiration_document', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'id_vehicule', referencedColumnName: 'id')]
    private ?Vehicule $vehicule = null;

    #[ORM\ManyToOne(targetEntity: DossierVehicule::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'id_dossier_vehicule', referencedColumnName: 'id_dossier_vehicule')]
    private ?DossierVehicule $dossier = null;

    #[ORM\Column(name: 'ref_document', length: 255)]
    private ?string $reference = null;

    #[ORM\Column(name: 'is_active')]
    private ?bool $isActive = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeDocument(): ?TypeDocument
    {
        return $this->typeDocument;
    }

    public function setTypeDocument(?TypeDocument $typeDocument): static
    {
        $this->typeDocument = $typeDocument;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): static
    {
        $this->vehicule = $vehicule;
        return $this;
    }

    public function getDossier(): ?DossierVehicule
    {
        return $this->dossier;
    }

    public function setDossier(?DossierVehicule $dossier): static
    {
        $this->dossier = $dossier;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
}