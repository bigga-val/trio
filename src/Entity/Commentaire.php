<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    /** Nom affiché pour les visiteurs non connectés. */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomVisiteur = null;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    /** Si false, le commentaire attend la modération de l'admin. */
    #[ORM\Column(options: ['default' => false])]
    private bool $isVisible = false;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    /** Utilisateur connecté (null si visiteur anonyme). */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getNomVisiteur(): ?string
    {
        return $this->nomVisiteur;
    }

    public function setNomVisiteur(?string $nomVisiteur): static
    {
        $this->nomVisiteur = $nomVisiteur;
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

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /** Retourne le nom à afficher (compte ou visiteur). */
    public function getNomAuteur(): string
    {
        if ($this->user !== null) {
            return $this->user->getNomComplet();
        }
        return $this->nomVisiteur ?? 'Visiteur';
    }
}
