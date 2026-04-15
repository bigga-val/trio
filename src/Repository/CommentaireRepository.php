<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * Retourne les commentaires visibles d'un produit.
     *
     * @return Commentaire[]
     */
    public function findVisiblesByProduit(int $produitId): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->andWhere('c.produit = :pid')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('pid', $produitId)
            ->setParameter('visible', true)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les commentaires en attente de modération (pour l'admin).
     *
     * @return Commentaire[]
     */
    public function findEnAttente(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.produit', 'p')
            ->addSelect('p')
            ->andWhere('c.isVisible = :visible')
            ->setParameter('visible', false)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
