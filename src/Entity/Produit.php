<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomProduit = null;

    #[ORM\Column]
    private ?int $prixProduit = null;


    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $ceatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?CategorieVehicule $categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $descrition = null;

    #[ORM\Column(length:255)]
    private $ImageProduit = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?Devise $devise = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?Services $services = null;

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomProduit(): ?string
    {
        return $this->nomProduit;
    }

    public function setNomProduit(string $nomProduit): static
    {
        $this->nomProduit = $nomProduit;

        return $this;
    }

    public function getPrixProduit(): ?int
    {
        return $this->prixProduit;
    }

    public function setPrixProduit(int $prixProduit): static
    {
        $this->prixProduit = $prixProduit;

        return $this;
    }


    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getCeatedAt(): ?\DateTimeImmutable
    {
        return $this->ceatedAt;
    }

    public function setCeatedAt(\DateTimeImmutable $ceatedAt): static
    {
        $this->ceatedAt = $ceatedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCategorie(): ?CategorieVehicule
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieVehicule $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getDescrition(): ?string
    {
        return $this->descrition;
    }

    public function setDescrition(string $descrition): static
    {
        $this->descrition = $descrition;

        return $this;
    }

    public function getImageProduit()
    {
        return $this->ImageProduit;
    }

    public function setImageProduit($imageProduit): static
    {
        $this->ImageProduit = $imageProduit;

        return $this;
    }

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getService(): ?Services
    {
        return $this->services;
    }

    public function setService(?Services $service): static
    {
        $this->services = $service;

        return $this;
    }



}
