<?php

namespace App\Entity;

use App\Repository\DossierVehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierVehiculeRepository::class)]
#[ORM\Table(name: 'dossier_vehicule')]
#[ORM\HasLifecycleCallbacks]
class DossierVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_dossier_vehicule')]
    private ?int $id = null;

    #[ORM\Column(name: 'ref_dossier', length: 255)]
    private ?string $refDossier = null;

    #[ORM\OneToMany(mappedBy: 'dossier', targetEntity: DocumentVehicule::class)]
    private Collection $documents;

    #[ORM\OneToOne(inversedBy: 'dossier', targetEntity: Vehicule::class)]
    #[ORM\JoinColumn(name: 'id_vehicule', referencedColumnName: 'id')]
    private ?Vehicule $vehicule = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->generateReference();
    }

    #[ORM\PrePersist]
    public function generateReference(): void
    {
        if (empty($this->refDossier)) {
            $prefix = 'DV-';
            $uniqueId = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $this->refDossier = $prefix . $uniqueId;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefDossier(): ?string
    {
        return $this->refDossier;
    }

    public function setRefDossier(string $refDossier): static
    {
        $this->refDossier = $refDossier;
        return $this;
    }

    /**
     * @return Collection<int, DocumentVehicule>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(DocumentVehicule $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setDossier($this);
        }

        return $this;
    }

    public function removeDocument(DocumentVehicule $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getDossier() === $this) {
                $document->setDossier(null);
            }
        }

        return $this;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }
    public function setVehicule(?Vehicule $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }
}
