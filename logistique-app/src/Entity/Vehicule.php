<?php

namespace App\Entity;

use App\Enum\CarburantType;
use App\Enum\ImmatriculationType;
use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $immatriculation_vehicule = null;

    #[ORM\Column]
    private ?int $puissance_vehicule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $chassis_vehicule = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $premiere_utilisation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiration_carte_grise = null;

    #[ORM\Column]
    private ?int $nb_place = null;

    #[ORM\Column(length: 255, enumType: CarburantType::class)]
    private ?CarburantType $type_carburant = null;

    #[ORM\Column]
    private ?float $capacite_consommation_vehicule = null;

    #[ORM\Column(length: 255, enumType: ImmatriculationType::class)]
    private ?ImmatriculationType $type_immatriculation = null;

    // Ajout du champ statut
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $statut = true;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MarqueVehicule $id_marque = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ModeleVehicule $modele_vehicule = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Entite $id_entite = null;

    #[ORM\OneToMany(mappedBy: 'id_vehicule', targetEntity: AffectationVehicule::class, orphanRemoval: true)]
    private Collection $affectationVehicules;

    #[ORM\OneToMany(mappedBy: 'vehicule', targetEntity: DocumentVehicule::class, orphanRemoval: true)]
    private Collection $documents;

    #[ORM\OneToOne(mappedBy: 'vehicule', targetEntity: DossierVehicule::class, cascade: ['persist', 'remove'])]
    private ?DossierVehicule $dossier = null;


    public function __construct()
    {
        $this->affectationVehicules = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->statut = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImmatriculationVehicule(): ?string
    {
        return $this->immatriculation_vehicule;
    }

    public function setImmatriculationVehicule(string $immatriculation_vehicule): static
    {
        $this->immatriculation_vehicule = $immatriculation_vehicule;

        return $this;
    }

    public function getPuissanceVehicule(): ?int
    {
        return $this->puissance_vehicule;
    }

    public function setPuissanceVehicule(int $puissance_vehicule): static
    {
        $this->puissance_vehicule = $puissance_vehicule;

        return $this;
    }

    public function getChassisVehicule(): ?string
    {
        return $this->chassis_vehicule;
    }

    public function setChassisVehicule(?string $chassis_vehicule): static
    {
        $this->chassis_vehicule = $chassis_vehicule;

        return $this;
    }

    public function getPremiereUtilisation(): ?\DateTimeInterface
    {
        return $this->premiere_utilisation;
    }

    public function setPremiereUtilisation(?\DateTimeInterface $premiere_utilisation): static
    {
        $this->premiere_utilisation = $premiere_utilisation;

        return $this;
    }

    public function getExpirationCarteGrise(): ?\DateTimeInterface
    {
        return $this->expiration_carte_grise;
    }

    public function setExpirationCarteGrise(?\DateTimeInterface $expiration_carte_grise): static
    {
        $this->expiration_carte_grise = $expiration_carte_grise;

        return $this;
    }

    public function getNbPlace(): ?int
    {
        return $this->nb_place;
    }

    public function setNbPlace(int $nb_place): static
    {
        $this->nb_place = $nb_place;

        return $this;
    }

    public function getTypeCarburant(): ?CarburantType
    {
        return $this->type_carburant;
    }

    public function setTypeCarburant(CarburantType $type_carburant): static
    {
        $this->type_carburant = $type_carburant;

        return $this;
    }

    public function getCapaciteConsommationVehicule(): ?float
    {
        return $this->capacite_consommation_vehicule;
    }

    public function setCapaciteConsommationVehicule(float $capacite_consommation_vehicule): static
    {
        $this->capacite_consommation_vehicule = $capacite_consommation_vehicule;

        return $this;
    }

    public function getTypeImmatriculation(): ?ImmatriculationType
    {
        return $this->type_immatriculation;
    }

    public function setTypeImmatriculation(ImmatriculationType $type_immatriculation): static
    {
        $this->type_immatriculation = $type_immatriculation;

        return $this;
    }

    // Correction : Mise à jour des getters et setters pour la nouvelle propriété
    public function getIdMarque(): ?MarqueVehicule
    {
        return $this->id_marque;
    }

    public function setIdMarque(?MarqueVehicule $id_marque): static
    {
        $this->id_marque = $id_marque;

        return $this;
    }

    public function getModeleVehicule(): ?ModeleVehicule
    {
        return $this->modele_vehicule;
    }

    public function setModeleVehicule(?ModeleVehicule $modele_vehicule): static
    {
        $this->modele_vehicule = $modele_vehicule;

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

    /**
     * @return Collection<int, AffectationVehicule>
     */
    public function getAffectationVehicules(): Collection
    {
        return $this->affectationVehicules;
    }

    public function addAffectationVehicule(AffectationVehicule $affectationVehicule): static
    {
        if (!$this->affectationVehicules->contains($affectationVehicule)) {
            $this->affectationVehicules->add($affectationVehicule);
            $affectationVehicule->setIdVehicule($this);
        }

        return $this;
    }

    public function removeAffectationVehicule(AffectationVehicule $affectationVehicule): static
    {
        if ($this->affectationVehicules->removeElement($affectationVehicule)) {
            // set the owning side to null (unless already changed)
            if ($affectationVehicule->getIdVehicule() === $this) {
                $affectationVehicule->setIdVehicule(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DocumentVehicule>
     */
    public function getDocumentVehicules(): Collection
    {
        return $this->documents;
    }

    public function addDocumentVehicule(DocumentVehicule $documentVehicule): static
    {
        if (!$this->documents->contains($documentVehicule)) {
            $this->documents->add($documentVehicule);
            $documentVehicule->setVehicule($this);
        }

        return $this;
    }

    public function removeDocumentVehicule(DocumentVehicule $documentVehicule): static
    {
        if ($this->documents->removeElement($documentVehicule)) {
            // set the owning side to null (unless already changed)
            if ($documentVehicule->getVehicule() === $this) {
                $documentVehicule->setVehicule(null);
            }
        }

        return $this;
    }

    public function getStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(bool $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    // Ajoutez cette méthode dans votre entité Vehicule (si elle n'existe pas déjà)
    public function __toString(): string
    {
        return $this->immatriculation_vehicule ?? 'Véhicule #' . $this->id;
    }

    public function getDossier(): ?DossierVehicule
    {
        return $this->dossier;
    }

    public function setDossier(?DossierVehicule $dossier): static
    {
        // unset the owning side of the relation if necessary
        if ($dossier === null && $this->dossier !== null) {
            $this->dossier->setVehicule(null);
        }

        // set the owning side of the relation if necessary
        if ($dossier !== null && $dossier->getVehicule() !== $this) {
            $dossier->setVehicule($this);
        }

        $this->dossier = $dossier;

        return $this;
    }
}
