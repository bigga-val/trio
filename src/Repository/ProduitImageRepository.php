<?php

namespace App\Repository;

use App\Entity\ProduitImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProduitImage>
 *
 * @method ProduitImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProduitImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProduitImage[]    findAll()
 * @method ProduitImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitImage::class);
    }

    public function add(ProduitImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProduitImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
