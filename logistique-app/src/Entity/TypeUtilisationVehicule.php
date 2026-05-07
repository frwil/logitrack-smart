<?php

namespace App\Entity;

use App\Repository\TypeUtilisationVehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeUtilisationVehiculeRepository::class)]
class TypeUtilisationVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\OneToMany(targetEntity: AffectationVehicule::class, mappedBy: 'id_type_utilisation')]
    private Collection $affectationVehicules;

    public function __construct()
    {
        $this->affectationVehicules = new ArrayCollection();
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
            $affectationVehicule->setIdTypeUtilisation($this);
        }

        return $this;
    }

    public function removeAffectationVehicule(AffectationVehicule $affectationVehicule): static
    {
        if ($this->affectationVehicules->removeElement($affectationVehicule)) {
            // set the owning side to null (unless already changed)
            if ($affectationVehicule->getIdTypeUtilisation() === $this) {
                $affectationVehicule->setIdTypeUtilisation(null);
            }
        }

        return $this;
    }
}
