<?php

namespace App\Entity;

use App\Repository\AffectationVehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationVehiculeRepository::class)]
class AffectationVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Correction: Changement de inversedBy de 'affectations' à 'affectationVehicules'
    #[ORM\ManyToOne(inversedBy: 'affectationVehicules')]
    private ?Vehicule $id_vehicule = null;

    #[ORM\ManyToOne(inversedBy: 'affectationVehicules')]
    private ?Chauffeur $id_chauffeur = null;

    #[ORM\ManyToOne(inversedBy: 'affectationVehicules')]
    private ?TypeUtilisationVehicule $id_type_utilisation = null;

    #[ORM\ManyToOne(inversedBy: 'affectationVehicules')]
    private ?ModeUtilisationVehicule $id_mode_utilisation = null;

    #[ORM\ManyToOne(inversedBy: 'affectationVehicules')]
    #[ORM\JoinColumn(name: 'id_region', referencedColumnName: 'id_region')]
    private ?Region $id_region = null;

    #[ORM\ManyToOne(inversedBy: 'affectationVehicules')]
    private ?Entite $id_entite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $objet_affectation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_debut_affectation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_fin_affectation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_affectation = null;

    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: ReleveKmsVehicule::class)]
    private Collection $releveKmsVehicules;

    #[ORM\Column]
    private ?bool $is_ferme = false;

    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: VidangeVehicule::class)]
    private Collection $vidanges;

    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: BonReparation::class)]
    private Collection $bonsReparation;

    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: Voyage::class)]
    private Collection $voyages;

    public function __construct()
    {
        $this->date_affectation = new \DateTimeImmutable();
        $this->releveKmsVehicules = new ArrayCollection();
        $this->vidanges = new ArrayCollection();
        $this->bonsReparation = new ArrayCollection();
        $this->voyages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdVehicule(): ?Vehicule
    {
        return $this->id_vehicule;
    }

    public function setIdVehicule(?Vehicule $id_vehicule): static
    {
        $this->id_vehicule = $id_vehicule;

        return $this;
    }

    public function getIdChauffeur(): ?Chauffeur
    {
        return $this->id_chauffeur;
    }

    public function setIdChauffeur(?Chauffeur $id_chauffeur): static
    {
        $this->id_chauffeur = $id_chauffeur;

        return $this;
    }

    public function getIdTypeUtilisation(): ?TypeUtilisationVehicule
    {
        return $this->id_type_utilisation;
    }

    public function setIdTypeUtilisation(?TypeUtilisationVehicule $id_type_utilisation): static
    {
        $this->id_type_utilisation = $id_type_utilisation;

        return $this;
    }

    public function getIdModeUtilisation(): ?ModeUtilisationVehicule
    {
        return $this->id_mode_utilisation;
    }

    public function setIdModeUtilisation(?ModeUtilisationVehicule $id_mode_utilisation): static
    {
        $this->id_mode_utilisation = $id_mode_utilisation;

        return $this;
    }

    public function getIdEntite(): ?Entite
    {
        return $this->id_entite;
    }

    public function setIdEntite(?Entite $id_entite): static
    {
        $this->id_entite = $id_entite;

        return $this;
    }

    public function getObjetAffectation(): ?string
    {
        return $this->objet_affectation;
    }

    public function setObjetAffectation(?string $objet_affectation): static
    {
        $this->objet_affectation = $objet_affectation;

        return $this;
    }

    public function getDateDebutAffectation(): ?\DateTimeInterface
    {
        return $this->date_debut_affectation;
    }

    public function setDateDebutAffectation(\DateTimeInterface $date_debut_affectation): static
    {
        $this->date_debut_affectation = $date_debut_affectation;

        return $this;
    }

    public function getDateFinAffectation(): ?\DateTimeInterface
    {
        return $this->date_fin_affectation;
    }

    public function setDateFinAffectation(?\DateTimeInterface $date_fin_affectation): static
    {
        $this->date_fin_affectation = $date_fin_affectation;

        return $this;
    }

    public function getIdRegion(): ?Region
    {
        return $this->id_region;
    }

    public function setIdRegion(?Region $id_region): static
    {
        $this->id_region = $id_region;

        return $this;
    }

    public function getDateAffectation(): ?\DateTimeInterface
    {
        return $this->date_affectation;
    }

    public function setDateAffectation(\DateTimeInterface $date_affectation): static
    {
        $this->date_affectation = $date_affectation;

        return $this;
    }

    public function isIsFerme(): ?bool
    {
        return $this->is_ferme;
    }

    public function setIsFerme(bool $is_ferme): static
    {
        $this->is_ferme = $is_ferme;

        return $this;
    }

    // Ajoutez cette méthode dans votre entité AffectationVehicule
    public function __toString(): string
    {
        $chauffeur = $this->getIdChauffeur();
        $vehicule = $this->getIdVehicule();

        $chauffeurName = $chauffeur ? $chauffeur->getPrenom() . ' ' . $chauffeur->getNom() : 'Chauffeur inconnu';
        $vehiculeInfo = $vehicule ? $vehicule->getImmatriculationVehicule() : 'Véhicule inconnu';

        return $chauffeurName . ' - ' . $vehiculeInfo;
    }

    public function getReleveKmsVehicules(): Collection
    {
        return $this->releveKmsVehicules;
    }

    public function addReleveKmsVehicule(ReleveKmsVehicule $releveKmsVehicule): static
    {
        if (!$this->releveKmsVehicules->contains($releveKmsVehicule)) {
            $this->releveKmsVehicules->add($releveKmsVehicule);
            $releveKmsVehicule->setAffectation($this);
        }

        return $this;
    }

    public function removeReleveKmsVehicule(ReleveKmsVehicule $releveKmsVehicule): static
    {
        if ($this->releveKmsVehicules->removeElement($releveKmsVehicule)) {
            // set the owning side to null (unless already changed)
            if ($releveKmsVehicule->getAffectation() === $this) {
                $releveKmsVehicule->setAffectation(null);
            }
        }

        return $this;
    }

    public function getVidanges(): Collection
    {
        return $this->vidanges;
    }

    public function addVidange(VidangeVehicule $vidange): static
    {
        if (!$this->vidanges->contains($vidange)) {
            $this->vidanges->add($vidange);
            $vidange->setAffectation($this);
        }

        return $this;
    }

    public function removeVidange(VidangeVehicule $vidange): static
    {
        if ($this->vidanges->removeElement($vidange)) {
            // set the owning side to null (unless already changed)
            if ($vidange->getAffectation() === $this) {
                $vidange->setAffectation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BonReparation>
     */
    public function getBonsReparation(): Collection
    {
        return $this->bonsReparation;
    }

    public function addBonReparation(BonReparation $bonReparation): static
    {
        if (!$this->bonsReparation->contains($bonReparation)) {
            $this->bonsReparation->add($bonReparation);
            $bonReparation->setAffectation($this);
        }

        return $this;
    }

    public function removeBonReparation(BonReparation $bonReparation): static
    {
        if ($this->bonsReparation->removeElement($bonReparation)) {
            // set the owning side to null (unless already changed)
            if ($bonReparation->getAffectation() === $this) {
                $bonReparation->setAffectation(null);
            }
        }

        return $this;
    }

    public function getVoyages(): Collection
    {
        return $this->voyages;
    }

    public function addVoyage(Voyage $voyage): static
    {
        if (!$this->voyages->contains($voyage)) {
            $this->voyages->add($voyage);
            $voyage->setAffectation($this);
        }

        return $this;
    }

    public function removeVoyage(Voyage $voyage): static
    {
        if ($this->voyages->removeElement($voyage)) {
            // set the owning side to null (unless already changed)
            if ($voyage->getAffectation() === $this) {
                $voyage->setAffectation(null);
            }
        }

        return $this;
    }
}
