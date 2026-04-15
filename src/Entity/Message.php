<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    /** Si false, le message n'a pas encore été lu par l'admin (ou le client pour les réponses). */
    #[ORM\Column(options: ['default' => false])]
    private bool $lu = false;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    /** Produit concerné par ce message. */
    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Produit $produit = null;

    /** Auteur du message (client ou admin). */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $expediteur = null;

    /** Message parent pour le système de réponses (null = message racine). */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parentMessage = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentMessage', cascade: ['remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $reponses;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
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

    public function isLu(): bool
    {
        return $this->lu;
    }

    public function setLu(bool $lu): static
    {
        $this->lu = $lu;
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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getExpediteur(): ?User
    {
        return $this->expediteur;
    }

    public function setExpediteur(?User $expediteur): static
    {
        $this->expediteur = $expediteur;
        return $this;
    }

    public function getParentMessage(): ?self
    {
        return $this->parentMessage;
    }

    public function setParentMessage(?self $parentMessage): static
    {
        $this->parentMessage = $parentMessage;
        return $this;
    }

    /** @return Collection<int, self> */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(self $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setParentMessage($this);
        }
        return $this;
    }

    public function removeReponse(self $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            if ($reponse->getParentMessage() === $this) {
                $reponse->setParentMessage(null);
            }
        }
        return $this;
    }

    /** Vérifie si c'est un message racine (pas une réponse). */
    public function isRacine(): bool
    {
        return $this->parentMessage === null;
    }
}
