<?php

namespace App\Entity;

use App\Repository\ChauffeurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChauffeurRepository::class)]
class Chauffeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\OneToMany(mappedBy: 'chauffeur', targetEntity: Permis::class, orphanRemoval: true)]
    private Collection $permis;

    // C'est la propriété que votre template doit utiliser.
    #[ORM\OneToMany(mappedBy: 'id_chauffeur', targetEntity: AffectationVehicule::class)]
    private Collection $affectationVehicules;

    #[ORM\Column(type: 'boolean')]
    private bool $estActif = true;

    public function __construct()
    {
        $this->permis = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

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
            $permi->setChauffeur($this);
        }

        return $this;
    }

    public function removePermi(Permis $permi): static
    {
        if ($this->permis->removeElement($permi)) {
            // set the owning side to null (unless already changed)
            if ($permi->getChauffeur() === $this) {
                $permi->setChauffeur(null);
            }
        }

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
            $affectationVehicule->setIdChauffeur($this);
        }

        return $this;
    }

    public function removeAffectationVehicule(AffectationVehicule $affectationVehicule): static
    {
        if ($this->affectationVehicules->removeElement($affectationVehicule)) {
            // set the owning side to null (unless already changed)
            if ($affectationVehicule->getIdChauffeur() === $this) {
                $affectationVehicule->setIdChauffeur(null);
            }
        }

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): static
    {
        $this->estActif = $estActif;
        return $this;
    }
}
