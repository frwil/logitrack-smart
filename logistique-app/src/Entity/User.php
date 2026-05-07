<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\OneToMany(targetEntity: VidangeVehicule::class, mappedBy: 'validePar')]
    private Collection $vidangesValidees;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id_region', nullable: true)]
    private ?Region $region = null;

    #[ORM\Column(type: 'json')]
    private array $permissions = [];

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLogin = null;

    #[ORM\Column(type: 'json')]
    private array $notificationPreferences = [];


    public function __construct()
    {
        $this->vidangesValidees = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->permissions = [];
        $this->notificationPreferences = [
            'documents' => true,
            'maintenance' => true,
            'trips' => true,
            'system' => true,
            'frequency' => 'realtime'
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    /**
     * @return Collection<int, VidangeVehicule>
     */
    public function getVidangesValidees(): Collection
    {
        return $this->vidangesValidees;
    }

    public function addVidangesValidee(VidangeVehicule $vidangesValidee): static
    {
        if (!$this->vidangesValidees->contains($vidangesValidee)) {
            $this->vidangesValidees->add($vidangesValidee);
            $vidangesValidee->setValidePar($this);
        }

        return $this;
    }

    public function removeVidangesValidee(VidangeVehicule $vidangesValidee): static
    {
        if ($this->vidangesValidees->removeElement($vidangesValidee)) {
            // set the owning side to null (unless already changed)
            if ($vidangesValidee->getValidePar() === $this) {
                $vidangesValidee->setValidePar(null);
            }
        }

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function __toString(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /**
     * Vérifie si l'utilisateur est un super administrateur
     */
    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->getRoles());
    }

    /**
     * Vérifie si l'utilisateur est un administrateur
     */
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function hasPermission(string $module, string $action): bool
    {
        // Super admin a tous les droits
        if (in_array('ROLE_SUPER_ADMIN', $this->roles)) {
            return true;
        }

        return isset($this->permissions[$module][$action]) && $this->permissions[$module][$action] === true;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeImmutable $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getNotificationPreferences(): array
    {
        return $this->notificationPreferences;
    }

    public function setNotificationPreferences(array $notificationPreferences): self
    {
        $this->notificationPreferences = $notificationPreferences;
        return $this;
    }
}
