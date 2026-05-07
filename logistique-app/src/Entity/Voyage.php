<?php

namespace App\Entity;

use App\Repository\VoyageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: 'voyage')]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'date_voyage', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateVoyage = null;

    #[ORM\ManyToOne(targetEntity: AffectationVehicule::class,inversedBy: 'voyages')]
    #[ORM\JoinColumn(name: 'id_affectation', referencedColumnName: 'id', nullable: true)]
    private ?AffectationVehicule $affectation = null;

    #[ORM\Column(name: 'qte_carburant', type: Types::BIGINT)]
    private ?int $quantiteCarburant = 0;

    #[ORM\Column(name: 'convoyeur', type: Types::STRING, length: 255, nullable: true)]
    private ?string $convoyeur = null;

    #[ORM\Column(name: 'titre_voyage', type: Types::STRING, length: 255)]
    private ?string $titre = null;

    #[ORM\ManyToOne(targetEntity: TypeChargementVoyage::class, inversedBy: 'voyages')]
    #[ORM\JoinColumn(name: 'id_type_chargement', referencedColumnName: 'id', nullable: true)]
    private ?TypeChargementVoyage $typeChargement = null;

    #[ORM\Column(name: 'qte_chargement', type: Types::BIGINT)]
    private ?int $quantiteChargement = 0;

    #[ORM\Column(name: 'commentaire_voyage', type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\OneToMany(mappedBy: 'voyage', targetEntity: VoyageVehicule::class, orphanRemoval: true)]
    private Collection $voyageVehicules;

    #[ORM\Column(name: 'km_depart', type: Types::INTEGER, nullable: true)]
    private ?int $kmDepart = null;

    #[ORM\Column(name: 'km_arrivee', type: Types::INTEGER, nullable: true)]
    private ?int $kmArrivee = null;

    #[ORM\Column(name: 'date_prevue_voyage', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePrevueVoyage = null;

    #[ORM\Column(name: 'heure_depart', type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $heureDepart = null;

#[ORM\Column(name: 'heure_arrivee', type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $heureArrivee = null;

    public function __construct()
    {
        $this->voyageVehicules = new ArrayCollection();
        $this->dateVoyage = new \DateTime();
        $this->datePrevueVoyage = new \DateTime();
        $this->heureDepart = new \DateTime();
        $this->heureArrivee = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateVoyage(): ?\DateTimeInterface
    {
        return $this->dateVoyage;
    }

    public function setDateVoyage(\DateTimeInterface $dateVoyage): static
    {
        $this->dateVoyage = $dateVoyage;
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

    public function getQuantiteCarburant(): ?int
    {
        return $this->quantiteCarburant;
    }

    public function setQuantiteCarburant(int $quantiteCarburant): static
    {
        $this->quantiteCarburant = $quantiteCarburant;
        return $this;
    }

    public function getConvoyeur(): ?string
    {
        return $this->convoyeur;
    }

    public function setConvoyeur(?string $convoyeur): static
    {
        $this->convoyeur = $convoyeur;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getTypeChargement(): ?TypeChargementVoyage
    {
        return $this->typeChargement;
    }

    public function setTypeChargement(?TypeChargementVoyage $typeChargement): static
    {
        $this->typeChargement = $typeChargement;
        return $this;
    }

    public function getQuantiteChargement(): ?int
    {
        return $this->quantiteChargement;
    }

    public function setQuantiteChargement(int $quantiteChargement): static
    {
        $this->quantiteChargement = $quantiteChargement;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    /**
     * @return Collection<int, VoyageVehicule>
     */
    public function getVoyageVehicules(): Collection
    {
        return $this->voyageVehicules;
    }

    public function addVoyageVehicule(VoyageVehicule $voyageVehicule): static
    {
        if (!$this->voyageVehicules->contains($voyageVehicule)) {
            $this->voyageVehicules->add($voyageVehicule);
            $voyageVehicule->setVoyage($this);
        }

        return $this;
    }

    public function removeVoyageVehicule(VoyageVehicule $voyageVehicule): static
    {
        if ($this->voyageVehicules->removeElement($voyageVehicule)) {
            // set the owning side to null (unless already changed)
            if ($voyageVehicule->getVoyage() === $this) {
                $voyageVehicule->setVoyage(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? 'Voyage #' . $this->id;
    }

    public function getKmDepart(): ?int
    {
        return $this->kmDepart;
    }

    public function setKmDepart(?int $kmDepart): static
    {
        $this->kmDepart = $kmDepart;
        return $this;
    }

    public function getKmArrivee(): ?int
    {
        return $this->kmArrivee;
    }

    public function setKmArrivee(?int $kmArrivee): static
    {
        $this->kmArrivee = $kmArrivee;
        return $this;
    }

    public function getDatePrevueVoyage(): ?\DateTimeInterface
    {
        return $this->datePrevueVoyage;
    }

    public function setDatePrevueVoyage(?\DateTimeInterface $datePrevueVoyage): static
    {
        $this->datePrevueVoyage = $datePrevueVoyage;
        return $this;
    }

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(?\DateTimeInterface $heureDepart): static
    {
        $this->heureDepart = $heureDepart;
        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(?\DateTimeInterface $heureArrivee): static
    {
        $this->heureArrivee = $heureArrivee;
        return $this;
    }

    // Méthode pour calculer la distance totale du voyage
    public function getDistanceTotale(): int
    {
        $distanceTotale = 0;
        foreach ($this->getVoyageVehicules() as $voyageVehicule) {
            $distanceTotale += $voyageVehicule->getDestination()->getDistance();
        }
        return $distanceTotale;
    }
}