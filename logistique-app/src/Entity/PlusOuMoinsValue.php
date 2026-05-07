<?php

namespace App\Entity;

use App\Repository\PlusOuMoinsValueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlusOuMoinsValueRepository::class)]
#[ORM\Table(name: 'plus_ou_moins_value')]
#[ORM\Index(columns: ['lib_plus_ou_moins_value'], name: 'idx_libelle')]
#[ORM\Index(columns: ['type_plus_ou_moins_value'], name: 'idx_type_valeur')]
#[ORM\Index(columns: ['est_actif'], name: 'idx_est_actif')]
#[ORM\HasLifecycleCallbacks]
class PlusOuMoinsValue
{
    // Types de valeurs constants pour plus de clarté
    public const TYPE_PLUS_VALUE = true;
    public const TYPE_MOINS_VALUE = false;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_plus_ou_moins_value')]
    private ?int $id = null;

    #[ORM\Column(name: 'lib_plus_ou_moins_value', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $libelle = null;

    #[ORM\Column(name: 'type_plus_ou_moins_value')]
    #[Assert\NotNull]
    private ?bool $typeValeur = null;

    #[ORM\OneToMany(mappedBy: 'plusOuMoinsValue', targetEntity: BonReparation::class)]
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
     * Retourne le type de valeur (true = plus-value, false = moins-value)
     */
    public function getTypeValeur(): ?bool
    {
        return $this->typeValeur;
    }

    /**
     * Définit le type de valeur (true = plus-value, false = moins-value)
     */
    public function setTypeValeur(bool $typeValeur): static
    {
        $this->typeValeur = $typeValeur;
        return $this;
    }

    /**
     * Méthode utilitaire pour savoir si c'est une plus-value
     */
    public function isPlusValue(): bool
    {
        return $this->typeValeur === self::TYPE_PLUS_VALUE;
    }

    /**
     * Méthode utilitaire pour savoir si c'est une moins-value
     */
    public function isMoinsValue(): bool
    {
        return $this->typeValeur === self::TYPE_MOINS_VALUE;
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
            $bonReparation->setPlusOuMoinsValue($this);
        }

        return $this;
    }

    public function removeBonReparation(BonReparation $bonReparation): static
    {
        if ($this->bonsReparation->removeElement($bonReparation)) {
            // set the owning side to null (unless already changed)
            if ($bonReparation->getPlusOuMoinsValue() === $this) {
                $bonReparation->setPlusOuMoinsValue(null);
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
     * Vérifie si cette valeur peut être supprimée (n'a pas de bons de réparation associés)
     */
    public function peutEtreSupprimee(): bool
    {
        return $this->bonsReparation->isEmpty();
    }
}