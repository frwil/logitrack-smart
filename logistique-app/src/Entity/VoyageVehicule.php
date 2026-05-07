<?php

namespace App\Entity;

use App\Repository\VoyageVehiculeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoyageVehiculeRepository::class)]
class VoyageVehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_voyage_vehicule')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'voyageVehicules')]
    #[ORM\JoinColumn(name: 'id_voyage', referencedColumnName: 'id', nullable: false)]
    private ?Voyage $voyage = null;

    #[ORM\ManyToOne(inversedBy: 'voyageVehicules')]
    #[ORM\JoinColumn(name: 'id_destination', referencedColumnName: 'id_destination', nullable: false)]
    private ?DestinationVoyage $destination = null;

    #[ORM\Column(name: 'commentaire_voyage_vehicule', type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): static
    {
        $this->voyage = $voyage;
        return $this;
    }

    public function getDestination(): ?DestinationVoyage
    {
        return $this->destination;
    }

    public function setDestination(?DestinationVoyage $destination): static
    {
        $this->destination = $destination;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function __toString(): string
    {
        return $this->voyage->getAffectation() . ' - ' . $this->destination;
    }
}