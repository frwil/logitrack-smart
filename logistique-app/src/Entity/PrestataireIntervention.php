<?php

namespace App\Entity;

use App\Repository\PrestataireInterventionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrestataireInterventionRepository::class)]
#[ORM\Table(name: 'prestataire_intervention')]
class PrestataireIntervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $nom = null;

    #[ORM\OneToMany(targetEntity: VidangeVehicule::class, mappedBy: 'prestataire')]
    private Collection $vidanges;

    #[ORM\OneToMany(targetEntity: BonReparation::class, mappedBy: 'prestataire')]
    private Collection $bonReparations;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^[+0-9\s\-\(\)]{0,20}$/')]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $specialites = null;

    #[ORM\Column]
    private ?bool $actif = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    public function __construct()
    {
        $this->bonReparations = new ArrayCollection();
        $this->vidanges = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getSpecialites(): ?string
    {
        return $this->specialites;
    }

    public function setSpecialites(?string $specialites): static
    {
        $this->specialites = $specialites;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    /**
     * @return Collection<int, VidangeVehicule>
     */
    public function getVidanges(): Collection
    {
        return $this->vidanges;
    }

    public function addVidange(VidangeVehicule $vidange): static
    {
        if (!$this->vidanges->contains($vidange)) {
            $this->vidanges->add($vidange);
            $vidange->setPrestataire($this);
        }
        return $this;
    }

    public function removeVidange(VidangeVehicule $vidange): static
    {
        if ($this->vidanges->removeElement($vidange)) {
            if ($vidange->getPrestataire() === $this) {
                $vidange->setPrestataire(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, BonReparation>
     */
    public function getBonReparations(): Collection
    {
        return $this->bonReparations;
    }

    public function addBonReparation(BonReparation $bonReparation): static
    {
        if (!$this->bonReparations->contains($bonReparation)) {
            $this->bonReparations->add($bonReparation);
            $bonReparation->setPrestataire($this);
        }
        return $this;
    }

    public function removeBonReparation(BonReparation $bonReparation): static
    {
        if ($this->bonReparations->removeElement($bonReparation)) {
            if ($bonReparation->getPrestataire() === $this) {
                $bonReparation->setPrestataire(null);
            }
        }
        return $this;
    }

    public function getCoutMoyenIntervention(): float
    {
        $total = 0;
        $count = 0;
        
        foreach ($this->bonReparations as $bon) {
            if ($bon->getMontantReparation() > 0) {
                $total += $bon->getMontantReparation();
                $count++;
            }
        }
        
        return $count > 0 ? round($total / $count, 2) : 0;
    }

    public function getDureeMoyenneIntervention(): float
    {
        $total = 0;
        $count = 0;
        
        foreach ($this->bonReparations as $bon) {
            if ($bon->getDureeReparation() > 0) {
                $total += $bon->getDureeReparation();
                $count++;
            }
        }
        
        return $count > 0 ? round($total / $count, 2) : 0;
    }

    public function getNombreInterventions(): int
    {
        return $this->bonReparations->count() + $this->vidanges->count();
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->dateModification = new \DateTime();
    }
}