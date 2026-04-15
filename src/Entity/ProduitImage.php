<?php

namespace App\Entity;

use App\Repository\ProduitImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitImageRepository::class)]
class ProduitImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Chemin relatif depuis /public/uploads/produits/ */
    #[ORM\Column(length: 255)]
    private ?string $cheminFichier = null;

    /** Ordre d'affichage dans la galerie (0 = premier). */
    #[ORM\Column(options: ['default' => 0])]
    private int $ordre = 0;

    /** Indique si cette image est l'image principale du produit. */
    #[ORM\Column(options: ['default' => false])]
    private bool $isPrincipale = false;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCheminFichier(): ?string
    {
        return $this->cheminFichier;
    }

    public function setCheminFichier(string $cheminFichier): static
    {
        $this->cheminFichier = $cheminFichier;
        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function isPrincipale(): bool
    {
        return $this->isPrincipale;
    }

    public function setIsPrincipale(bool $isPrincipale): static
    {
        $this->isPrincipale = $isPrincipale;
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

    /** Retourne le chemin complet pour usage dans les templates Twig. */
    public function getUrlPublique(): string
    {
        return '/uploads/produits/' . $this->cheminFichier;
    }
}
