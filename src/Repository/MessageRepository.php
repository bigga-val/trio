<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Retourne les messages racines (non-réponses) d'un utilisateur, groupés par produit.
     *
     * @return Message[]
     */
    public function findRacinesParUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.produit', 'p')
            ->addSelect('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->andWhere('m.expediteur = :user')
            ->andWhere('m.parentMessage IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le fil de conversation complet (message + réponses) pour un message racine.
     *
     * @return Message[]
     */
    public function findFilConversation(int $messageId): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.expediteur', 'u')
            ->addSelect('u')
            ->andWhere('m.id = :id OR m.parentMessage = :id')
            ->setParameter('id', $messageId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les messages non lus pour l'admin (tous les messages clients).
     */
    public function countNonLus(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.lu = :lu')
            ->andWhere('m.parentMessage IS NULL')
            ->setParameter('lu', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
