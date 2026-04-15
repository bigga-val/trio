<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /** Prix en USD. */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isDisponible = true;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    /** @var Collection<int, ProduitImage> */
    #[ORM\OneToMany(targetEntity: ProduitImage::class, mappedBy: 'produit', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $images;

    /** @var Collection<int, Commentaire> */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'produit', cascade: ['remove'])]
    private Collection $commentaires;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'produit', cascade: ['remove'])]
    private Collection $messages;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function isDisponible(): bool
    {
        return $this->isDisponible;
    }

    public function setIsDisponible(bool $isDisponible): static
    {
        $this->isDisponible = $isDisponible;
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

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    /** @return Collection<int, ProduitImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProduitImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduit($this);
        }
        return $this;
    }

    public function removeImage(ProduitImage $image): static
    {
        $this->images->removeElement($image);
        return $this;
    }

    /** Retourne l'image principale du produit, ou null si aucune image. */
    public function getImagePrincipale(): ?ProduitImage
    {
        foreach ($this->images as $image) {
            if ($image->isPrincipale()) {
                return $image;
            }
        }
        return $this->images->first() ?: null;
    }

    /** @return Collection<int, Commentaire> */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    /** @return Collection<int, Message> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }
}
