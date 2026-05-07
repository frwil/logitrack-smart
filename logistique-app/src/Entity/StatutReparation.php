<?php

namespace App\Entity;

use App\Repository\StatutReparationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StatutReparationRepository::class)]
#[ORM\Table(name: 'statut_reparation')]
#[ORM\Index(columns: ['libelle'], name: 'idx_libelle')]
#[ORM\Index(columns: ['ordre'], name: 'idx_ordre')]
#[ORM\Index(columns: ['est_actif'], name: 'idx_est_actif')]
#[ORM\HasLifecycleCallbacks]
class StatutReparation
{
    // Statuts prédéfinis constants pour plus de cohérence
    public const EN_ATTENTE = 'En attente';
    public const EN_COURS = 'En cours';
    public const TERMINE = 'Terminé';
    public const ANNULE = 'Annulé';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Length(max: 7)]
    #[Assert\Regex(pattern: '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/')]
    private ?string $couleur = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $ordre = null;

    #[ORM\OneToMany(mappedBy: 'statut', targetEntity: BonReparation::class)]
    private Collection $bonsReparation;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(type: 'boolean')]
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
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
            $bonReparation->setStatut($this);
        }
        return $this;
    }

    public function removeBonReparation(BonReparation $bonReparation): static
    {
        if ($this->bonsReparation->removeElement($bonReparation)) {
            if ($bonReparation->getStatut() === $this) {
                $bonReparation->setStatut(null);
            }
        }
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

    // Méthodes utilitaires
    public function estUtilisable(): bool
    {
        return $this->estActif;
    }

    public function getNombreBonsReparation(): int
    {
        return $this->bonsReparation->count();
    }

    /**
     * Retourne la couleur par défaut en fonction du libellé si aucune couleur n'est définie
     */
    public function getCouleurAvecDefaut(): string
    {
        if ($this->couleur) {
            return $this->couleur;
        }

        // Couleurs par défaut basées sur les constants
        $couleursParDefaut = [
            self::EN_ATTENTE => '#ffc107', // Jaune
            self::EN_COURS => '#17a2b8',   // Bleu
            self::TERMINE => '#28a745',    // Vert
            self::ANNULE => '#dc3545',     // Rouge
        ];

        return $couleursParDefaut[$this->libelle] ?? '#6c757d'; // Gris par défaut
    }

    /**
     * Vérifie si ce statut est un statut final (Terminé ou Annulé)
     */
    public function isStatutFinal(): bool
    {
        return in_array($this->libelle, [self::TERMINE, self::ANNULE], true);
    }

    /**
     * Vérifie si ce statut est le statut initial (En attente)
     */
    public function isStatutInitial(): bool
    {
        return $this->libelle === self::EN_ATTENTE;
    }
}
