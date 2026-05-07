<?php

namespace App\Entity;

use App\Repository\DestinationVoyageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DestinationVoyageRepository::class)]
class DestinationVoyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_destination')]
    private ?int $id = null;

    #[ORM\Column(name: 'lieu_depart', length: 255)]
    private ?string $lieuDepart = null;

    #[ORM\Column(name: 'lieu_arrivee', length: 255)]
    private ?string $lieuArrivee = null;

    #[ORM\Column(name: 'lib_destination', length: 255, unique: true)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'distance_destination')]
    private ?int $distance = 0;

    #[ORM\ManyToOne(inversedBy: 'destinationVoyages')]
    #[ORM\JoinColumn(name: 'id_region', referencedColumnName: 'id_region')]
    private ?Region $region = null;

    #[ORM\OneToMany(mappedBy: 'destination', targetEntity: VoyageVehicule::class)]
    private Collection $voyageVehicules;

    public function __construct()
    {
        $this->voyageVehicules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        // Retourne la concaténation des lieux si le libellé n'est pas défini
        if ($this->libelle === null && $this->lieuDepart !== null && $this->lieuArrivee !== null) {
            return $this->lieuDepart . ' - ' . $this->lieuArrivee;
        }
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getLieuDepart(): ?string
    {
        return $this->lieuDepart;
    }

    public function setLieuDepart(string $lieuDepart): static
    {
        $this->lieuDepart = $lieuDepart;
        
        // Met à jour le libellé automatiquement
        if ($this->lieuArrivee !== null) {
            $this->libelle = $lieuDepart . ' - ' . $this->lieuArrivee;
        }
        
        return $this;
    }

    public function getLieuArrivee(): ?string
    {
        return $this->lieuArrivee;
    }

    public function setLieuArrivee(string $lieuArrivee): static
    {
        $this->lieuArrivee = $lieuArrivee;
        
        // Met à jour le libellé automatiquement
        if ($this->lieuDepart !== null) {
            $this->libelle = $this->lieuDepart . ' - ' . $lieuArrivee;
        }
        
        return $this;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): static
    {
        $this->distance = $distance;
        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): static
    {
        $this->region = $region;
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
            $voyageVehicule->setDestination($this);
        }

        return $this;
    }

    public function removeVoyageVehicule(VoyageVehicule $voyageVehicule): static
    {
        if ($this->voyageVehicules->removeElement($voyageVehicule)) {
            // set the owning side to null (unless already changed)
            if ($voyageVehicule->getDestination() === $this) {
                $voyageVehicule->setDestination(null);
            }
        }

        return $this;
    }
    
    public function __toString(): string
    {
        return $this->getLibelle() ?? '';
    }
}