<?php

namespace App\Entity;

use App\Repository\RegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegionRepository::class)]
class Region
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_region')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\OneToMany(targetEntity: AffectationVehicule::class, mappedBy: 'id_region')]
    private Collection $affectationVehicules;

    #[ORM\OneToMany(targetEntity: DestinationVoyage::class, mappedBy: 'region')]
    private Collection $destinationVoyages;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'region')]
    private Collection $users;


    public function __construct()
    {
        $this->affectationVehicules = new ArrayCollection();
        $this->destinationVoyages = new ArrayCollection();
        $this->users = new ArrayCollection();
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
            $affectationVehicule->setIdRegion($this);
        }

        return $this;
    }

    public function removeAffectationVehicule(AffectationVehicule $affectationVehicule): static
    {
        if ($this->affectationVehicules->removeElement($affectationVehicule)) {
            // set the owning side to null (unless already changed)
            if ($affectationVehicule->getIdRegion() === $this) {
                $affectationVehicule->setIdRegion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DestinationVoyage>
     */
    public function getDestinationVoyages(): Collection
    {
        return $this->destinationVoyages;
    }

    public function addDestinationVoyage(DestinationVoyage $destinationVoyage): static
    {
        if (!$this->destinationVoyages->contains($destinationVoyage)) {
            $this->destinationVoyages->add($destinationVoyage);
            $destinationVoyage->setRegion($this);
        }

        return $this;
    }

    public function removeDestinationVoyage(DestinationVoyage $destinationVoyage): static
    {
        if ($this->destinationVoyages->removeElement($destinationVoyage)) {
            // set the owning side to null (unless already changed)
            if ($destinationVoyage->getRegion() === $this) {
                $destinationVoyage->setRegion(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setRegion($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getRegion() === $this) {
                $user->setRegion(null);
            }
        }

        return $this;
    }
}
