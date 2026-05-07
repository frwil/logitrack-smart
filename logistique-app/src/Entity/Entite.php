<?php

namespace App\Entity;

use App\Repository\EntiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntiteRepository::class)]
class Entite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $statut = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'id_entite', targetEntity: AffectationVehicule::class)]
    private Collection $affectationVehicules;

    // Ajout de la relation manquante
    #[ORM\OneToMany(mappedBy: 'id_entite', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    public function __construct()
    {
        $this->affectationVehicules = new ArrayCollection();
        $this->vehicules = new ArrayCollection();
        $this->statut = true; // Valeur par défaut
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
            $affectationVehicule->setIdEntite($this);
        }

        return $this;
    }

    public function removeAffectationVehicule(AffectationVehicule $affectationVehicule): static
    {
        if ($this->affectationVehicules->removeElement($affectationVehicule)) {
            // set the owning side to null (unless already changed)
            if ($affectationVehicule->getIdEntite() === $this) {
                $affectationVehicule->setIdEntite(null);
            }
        }

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
            $vehicule->setIdEntite($this);
        }

        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): static
    {
        if ($this->vehicules->removeElement($vehicule)) {
            // set the owning side to null (unless already changed)
            if ($vehicule->getIdEntite() === $this) {
                $vehicule->setIdEntite(null);
            }
        }

        return $this;
    }
}