<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * Retourne les produits disponibles avec leurs images (évite le N+1).
     *
     * @return Produit[]
     */
    public function findDisponiblesAvecImages(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->andWhere('p.isDisponible = :dispo')
            ->setParameter('dispo', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre les produits par catégorie avec recherche textuelle et pagination.
     *
     * @return Produit[]
     */
    public function findFiltered(?int $categorieId, ?string $recherche, int $page = 1, int $parPage = 12): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->andWhere('p.isDisponible = :dispo')
            ->setParameter('dispo', true);

        if ($categorieId !== null) {
            $qb->andWhere('c.id = :catId')->setParameter('catId', $categorieId);
        }

        if ($recherche !== null && $recherche !== '') {
            $qb->andWhere('p.titre LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $recherche . '%');
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $parPage)
            ->setMaxResults($parPage)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les produits filtrés (pour la pagination).
     */
    public function countFiltered(?int $categorieId, ?string $recherche): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.categorie', 'c')
            ->andWhere('p.isDisponible = :dispo')
            ->setParameter('dispo', true);

        if ($categorieId !== null) {
            $qb->andWhere('c.id = :catId')->setParameter('catId', $categorieId);
        }

        if ($recherche !== null && $recherche !== '') {
            $qb->andWhere('p.titre LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $recherche . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Retourne les N derniers produits pour la homepage (vedettes / offres).
     *
     * @return Produit[]
     */
    public function findDerniers(int $limite = 6): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->andWhere('p.isDisponible = :dispo')
            ->setParameter('dispo', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }
}
