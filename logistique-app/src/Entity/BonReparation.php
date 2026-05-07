<?php

namespace App\Entity;

use App\Repository\BonReparationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonReparationRepository::class)]
#[ORM\Table(name: 'bons_reparation')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['num_bon_reparation'], name: 'idx_numero')]
#[ORM\Index(columns: ['date_entree'], name: 'idx_date_entree')]
#[ORM\Index(columns: ['date_prevue_sortie'], name: 'idx_date_prevue_sortie')]
#[ORM\Index(columns: ['cloture_reparation'], name: 'idx_cloture')]
class BonReparation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_bon_reparation')]
    private ?int $id = null;

    #[ORM\Column(name: 'num_bon_reparation', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $numero = null;

    #[ORM\ManyToOne(targetEntity: AffectationVehicule::class, inversedBy: 'bonsReparation')]
    #[ORM\JoinColumn(name: 'id_affectation_vehicule', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull]
    private ?AffectationVehicule $affectation = null;

    #[ORM\Column(name: 'date_entree', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    #[Assert\LessThanOrEqual('today')]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $diagnostic = null;

    #[ORM\Column(name: 'type_execution')]
    #[Assert\NotNull]
    private ?bool $typeExecution = null;

    #[ORM\ManyToOne(targetEntity: PrestataireIntervention::class, inversedBy: 'bonReparations')]
    #[ORM\JoinColumn(name: 'prestataire_id', referencedColumnName: 'id')]
    private ?PrestataireIntervention $prestataire = null;

    #[ORM\Column(name: 'montant_reparation')]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $montantReparation = null;

    #[ORM\ManyToOne(targetEntity: PlusOuMoinsValue::class, inversedBy: 'bonsReparation')]
    #[ORM\JoinColumn(name: 'plus_ou_moins_value_id', referencedColumnName: 'id_plus_ou_moins_value', nullable: true)]
    private ?PlusOuMoinsValue $plusOuMoinsValue = null;

    #[ORM\Column(name: 'plus_ou_moins_value_valeur')]
    #[Assert\NotNull]
    private ?int $plusOuMoinsValueValeur = 0;

    #[ORM\Column(name: 'destination_bon', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $destination = null;

    #[ORM\Column(name: 'duree_reparation')]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $dureeReparation = null;

    #[ORM\Column(name: 'date_justification', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateEntree')]
    private ?\DateTimeInterface $dateJustification = null;

    #[ORM\ManyToOne(targetEntity: CentreCout::class, inversedBy: 'bonsReparation')]
    #[ORM\JoinColumn(name: 'id_centre_cout', referencedColumnName: 'id_centre_cout')]
    private ?CentreCout $centreCout = null;

    #[ORM\Column(name: 'date_prevue_sortie', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateEntree')]
    private ?\DateTimeInterface $datePrevueSortie = null;

    #[ORM\Column(name: 'date_fin_reparation', type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateEntree')]
    private ?\DateTimeInterface $dateFinReparation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(name: 'cloture_reparation')]
    private ?bool $cloture = false;

    #[ORM\ManyToOne(targetEntity: StatutReparation::class, inversedBy: 'bonsReparation')]
    #[ORM\JoinColumn(name: 'id_statut', referencedColumnName: 'id', nullable: true)]
    private ?StatutReparation $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->plusOuMoinsValueValeur = 0;
        $this->cloture = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;
        return $this;
    }

    public function getAffectation(): ?AffectationVehicule
    {
        return $this->affectation;
    }

    public function setAffectation(?AffectationVehicule $affectation): static
    {
        $this->affectation = $affectation;
        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(\DateTimeInterface $dateEntree): static
    {
        $this->dateEntree = $dateEntree;
        return $this;
    }

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(string $diagnostic): static
    {
        $this->diagnostic = $diagnostic;
        return $this;
    }

    public function isTypeExecution(): ?bool
    {
        return $this->typeExecution;
    }

    public function setTypeExecution(bool $typeExecution): static
    {
        $this->typeExecution = $typeExecution;
        return $this;
    }

    public function getPrestataire(): ?PrestataireIntervention
    {
        return $this->prestataire;
    }

    public function setPrestataire(?PrestataireIntervention $prestataire): static
    {
        $this->prestataire = $prestataire;
        return $this;
    }

    public function getMontantReparation(): ?int
    {
        return $this->montantReparation;
    }

    public function setMontantReparation(int $montantReparation): static
    {
        $this->montantReparation = $montantReparation;
        return $this;
    }

    public function getPlusOuMoinsValue(): ?PlusOuMoinsValue
    {
        return $this->plusOuMoinsValue;
    }

    public function setPlusOuMoinsValue(?PlusOuMoinsValue $plusOuMoinsValue): static
    {
        $this->plusOuMoinsValue = $plusOuMoinsValue;
        return $this;
    }

    public function getPlusOuMoinsValueValeur(): ?int
    {
        return $this->plusOuMoinsValueValeur;
    }

    public function setPlusOuMoinsValueValeur(int $plusOuMoinsValueValeur): static
    {
        $this->plusOuMoinsValueValeur = $plusOuMoinsValueValeur;
        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;
        return $this;
    }

    public function getDureeReparation(): ?int
    {
        return $this->dureeReparation;
    }

    public function setDureeReparation(int $dureeReparation): static
    {
        $this->dureeReparation = $dureeReparation;
        return $this;
    }

    public function getDateJustification(): ?\DateTimeInterface
    {
        return $this->dateJustification;
    }

    public function setDateJustification(\DateTimeInterface $dateJustification): static
    {
        $this->dateJustification = $dateJustification;
        return $this;
    }

    public function getCentreCout(): ?CentreCout
    {
        return $this->centreCout;
    }

    public function setCentreCout(?CentreCout $centreCout): static
    {
        $this->centreCout = $centreCout;
        return $this;
    }

    public function getDatePrevueSortie(): ?\DateTimeInterface
    {
        return $this->datePrevueSortie;
    }

    public function setDatePrevueSortie(\DateTimeInterface $datePrevueSortie): static
    {
        $this->datePrevueSortie = $datePrevueSortie;
        return $this;
    }

    public function getDateFinReparation(): ?\DateTimeInterface
    {
        return $this->dateFinReparation;
    }

    public function setDateFinReparation(?\DateTimeInterface $dateFinReparation): static
    {
        $this->dateFinReparation = $dateFinReparation;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;
        return $this;
    }

    public function isCloture(): ?bool
    {
        return $this->cloture;
    }

    public function setCloture(bool $cloture): static
    {
        $this->cloture = $cloture;
        return $this;
    }

    public function getStatut(): ?StatutReparation
    {
        return $this->statut;
    }

    public function setStatut(?StatutReparation $statut): static
    {
        $this->statut = $statut;
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

    public function __toString(): string
    {
        return $this->numero ?? 'Bon de réparation #' . $this->id;
    }

    public function getCoutTotal(): int
    {
        return $this->montantReparation + $this->plusOuMoinsValueValeur;
    }

    public function getDureeEffective(): int
    {
        if ($this->dateEntree && $this->dateFinReparation) {
            $interval = $this->dateEntree->diff($this->dateFinReparation);
            return $interval->days;
        }

        return $this->dureeReparation;
    }

    public function getRetard(): int
    {
        if ($this->datePrevueSortie && $this->dateFinReparation) {
            $interval = $this->datePrevueSortie->diff($this->dateFinReparation);
            return $this->dateFinReparation > $this->datePrevueSortie ? $interval->days : 0;
        }

        return 0;
    }

    public function estEnRetard(): bool
    {
        if (!$this->datePrevueSortie || $this->cloture) {
            return false;
        }

        $aujourdhui = new \DateTime();
        return $aujourdhui > $this->datePrevueSortie;
    }

    public function getJoursRestants(): int
    {
        if (!$this->datePrevueSortie || $this->cloture) {
            return 0;
        }

        $aujourdhui = new \DateTime();
        $interval = $aujourdhui->diff($this->datePrevueSortie);

        return $aujourdhui <= $this->datePrevueSortie ? $interval->days : -$interval->days;
    }

    #[ORM\PrePersist]
    public function genererNumeroAuto(): void
    {
        if (empty($this->numero)) {
            $prefixe = 'BR-' . date('YmdHis');
            $suffixe = rand(10, 99);
            $this->numero = $prefixe . '-' . $suffixe;
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->dateModification = new \DateTime();

        if ($this->dateCreation === null) {
            $this->dateCreation = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function verifierCloture(): void
    {
        // Si le statut est "Terminé" ou "Annulé", on clôture automatiquement le bon
        if ($this->statut && in_array($this->statut->getLibelle(), [StatutReparation::TERMINE, StatutReparation::ANNULE])) {
            $this->cloture = true;

            // Si c'est terminé et que la date de fin n'est pas encore définie, on la définit
            if ($this->statut->getLibelle() === StatutReparation::TERMINE && !$this->dateFinReparation) {
                $this->dateFinReparation = new \DateTime();
            }
        }
    }
}