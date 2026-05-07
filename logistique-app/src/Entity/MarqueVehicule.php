<?php

namespace App\Entity;

use App\Repository\MarqueVehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarqueVehiculeRepository::class)]
class MarqueVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $nom_marque = null;

    #[ORM\OneToMany(mappedBy: 'id_marque', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    // Correction : Changement du mappedBy pour correspondre au champ dans ModeleVehicule
    #[ORM\OneToMany(mappedBy: 'id_marque', targetEntity: ModeleVehicule::class)]
    private Collection $modeleVehicules;

    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
        $this->modeleVehicules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomMarque(): ?string
    {
        return $this->nom_marque;
    }

    public function setNomMarque(string $nom_marque): static
    {
        $this->nom_marque = $nom_marque;

        return $this;
    }

    /**
     * @return Collection<int, Vehicule>
     */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    public function addVehicule(Vehicule $vehicule): static
    {
        if (!$this->vehicules->contains($vehicule)) {
            $this->vehicules->add($vehicule);
            $vehicule->setIdMarque($this);
        }

        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): static
    {
        if ($this->vehicules->removeElement($vehicule)) {
            // set the owning side to null (unless already changed)
            if ($vehicule->getIdMarque() === $this) {
                $vehicule->setIdMarque(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ModeleVehicule>
     */
    public function getModeleVehicules(): Collection
    {
        return $this->modeleVehicules;
    }

    // Correction : Mise à jour des méthodes pour utiliser le bon nom de setter
    public function addModeleVehicule(ModeleVehicule $modeleVehicule): static
    {
        if (!$this->modeleVehicules->contains($modeleVehicule)) {
            $this->modeleVehicules->add($modeleVehicule);
            $modeleVehicule->setIdMarque($this);
        }

        return $this;
    }

    public function removeModeleVehicule(ModeleVehicule $modeleVehicule): static
    {
        if ($this->modeleVehicules->removeElement($modeleVehicule)) {
            // set the owning side to null (unless already changed)
            if ($modeleVehicule->getIdMarque() === $this) {
                $modeleVehicule->setIdMarque(null);
            }
        }

        return $this;
    }
}