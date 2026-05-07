<?php

namespace App\Entity;

use App\Repository\TypeDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;



#[ORM\Entity(repositoryClass: TypeDocumentRepository::class)]
#[ORM\Table(name: 'document_vehicule')]
class TypeDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_document')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_document', length: 255)]
    private ?string $nom = null;

    #[ORM\Column(name: 'validite_document')]
    private ?int $validite = null;

    #[ORM\OneToMany(mappedBy: 'typeDocument', targetEntity: DocumentVehicule::class)]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
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

    public function getValidite(): ?int
    {
        return $this->validite;
    }

    public function setValidite(int $validite): static
    {
        $this->validite = $validite;
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    /**
     * @return Collection<int, Voyage>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(DocumentVehicule $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setTypeDocument($this);
        }

        return $this;
    }

    public function removeDocument(DocumentVehicule $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getTypeDocument() === $this) {
                $document->setTypeDocument(null);
            }
        }

        return $this;
    }
}
