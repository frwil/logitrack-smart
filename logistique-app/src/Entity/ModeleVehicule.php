<?php

namespace App\Entity;

use App\Repository\ModeleVehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModeleVehiculeRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_modele_for_marque', columns: ['nom_modele', 'marque_vehicule_id'])]
class ModeleVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_modele = null;

    #[ORM\OneToMany(mappedBy: 'modele_vehicule', targetEntity: Vehicule::class)]
    private Collection $vehicules;

    #[ORM\ManyToOne(inversedBy: 'modeleVehicules')]
    #[ORM\JoinColumn(name: 'marque_vehicule_id', referencedColumnName: 'id')]
    private ?MarqueVehicule $id_marque = null;

    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomModele(): ?string
    {
        return $this->nom_modele;
    }

    public function setNomModele(string $nom_modele): static
    {
        $this->nom_modele = $nom_modele;

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
            $vehicule->setModeleVehicule($this);
        }

        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): static
    {
        if ($this->vehicules->removeElement($vehicule)) {
            // set the owning side to null (unless already changed)
            if ($vehicule->getModeleVehicule() === $this) {
                $vehicule->setModeleVehicule(null);
            }
        }

        return $this;
    }

    public function getIdMarque(): ?MarqueVehicule
    {
        return $this->id_marque;
    }

    public function setIdMarque(?MarqueVehicule $id_marque): static
    {
        $this->id_marque = $id_marque;

        return $this;
    }
}