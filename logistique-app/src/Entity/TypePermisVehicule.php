<?php

namespace App\Entity;

use App\Repository\TypePermisVehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypePermisVehiculeRepository::class)]
class TypePermisVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $libelle_permis = null;

    // Correction: Ajout de la relation vers Permis
    #[ORM\OneToMany(mappedBy: 'typePermisVehicule', targetEntity: Permis::class)]
    private Collection $permis;

    public function __construct()
    {
        $this->permis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibellePermis(): ?string
    {
        return $this->libelle_permis;
    }

    public function setLibellePermis(string $libelle_permis): static
    {
        $this->libelle_permis = $libelle_permis;

        return $this;
    }

    /**
     * @return Collection<int, Permis>
     */
    public function getPermis(): Collection
    {
        return $this->permis;
    }

    public function addPermi(Permis $permi): static
    {
        if (!$this->permis->contains($permi)) {
            $this->permis->add($permi);
            $permi->setTypePermisVehicule($this);
        }

        return $this;
    }

    public function removePermi(Permis $permi): static
    {
        if ($this->permis->removeElement($permi)) {
            // set the owning side to null (unless already changed)
            if ($permi->getTypePermisVehicule() === $this) {
                $permi->setTypePermisVehicule(null);
            }
        }

        return $this;
    }
}
