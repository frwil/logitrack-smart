<?php

namespace App\Entity;

use App\Repository\CentreCoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CentreCoutRepository::class)]
#[ORM\Table(name: 'centre_couts')]
#[ORM\Index(columns: ['lib_centre_cout'], name: 'idx_libelle')]
#[ORM\Index(columns: ['est_actif'], name: 'idx_est_actif')]
#[ORM\HasLifecycleCallbacks]
class CentreCout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_centre_cout')]
    private ?int $id = null;

    #[ORM\Column(name: 'lib_centre_cout', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $libelle = null;

    #[ORM\OneToMany(mappedBy: 'centreCout', targetEntity: BonReparation::class)]
    private Collection $bonsReparation;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN)]
    private bool $estActif = true;

    public function __construct()
    {
        $this->bonsReparation = new ArrayCollection();
        $this->dateCreation = new \DateTime();
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

    /**
     * @return Collection<int, BonReparation>
     */
    public function getBonsReparation(): Collection
    {
        return $this->bonsReparation;
    }

    public function addBonReparation(BonReparation $bonReparation): static
    {
        if (!$this->bonsReparation->contains($bonReparation)) {
            $this->bonsReparation->add($bonReparation);
            $bonReparation->setCentreCout($this);
        }

        return $this;
    }

    public function removeBonReparation(BonReparation $bonReparation): static
    {
        if ($this->bonsReparation->removeElement($bonReparation)) {
            // set the owning side to null (unless already changed)
            if ($bonReparation->getCentreCout() === $this) {
                $bonReparation->setCentreCout(null);
            }
        }

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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): static
    {
        $this->estActif = $estActif;
        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->dateModification = new \DateTime();
    }

    /**
     * Retourne le nombre de bons de réparation associés
     */
    public function getNombreBonsReparation(): int
    {
        return $this->bonsReparation->count();
    }

    /**
     * Vérifie si ce centre de coût peut être supprimé (n'a pas de bons de réparation associés)
     */
    public function peutEtreSupprime(): bool
    {
        return $this->bonsReparation->isEmpty();
    }

    /**
     * Retourne le coût total des réparations pour ce centre de coût
     */
    public function getCoutTotalReparations(): float
    {
        $total = 0;
        
        foreach ($this->bonsReparation as $bon) {
            $total += $bon->getCoutTotal();
        }
        
        return $total;
    }
}