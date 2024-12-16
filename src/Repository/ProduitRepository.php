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

    //    /**
    //     * @return Produit[] Returns an array of Produit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Produit
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function getIndexForIntrants(){
        $query = $this->getEntityManager()->createQuery(
           '
           SELECT p.nomProduit, p.prixProduit, p.ImageProduit, p.descrition, p.id, p.localisation
            FROM App\Entity\Produit p WHERE  p.categorie = (
              SELECT c.id 
              From App\Entity\categorieVehicule c 
              WHERE c.nomCategorie=:nom
            )  order by p.nomProduit DESC
            ');
            return $query->setParameter('nom','vehicules')->setMaxResults(4)->getResult();
      }


}
