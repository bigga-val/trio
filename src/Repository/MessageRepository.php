<?php

namespace App\Repository;

use App\Entity\Message;
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

    public function findAllMessages()
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestConversations()
    {
        // Récupérer le dernier message de chaque client (groupé par client)
        return $this->createQueryBuilder('m')
            ->select('m')
            ->where('m.client IS NOT NULL')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findConversationsByClientName(string $clientName)
    {
        return $this->createQueryBuilder('m')
            ->where('m.nomAuteur = :nom')
            ->setParameter('nom', $clientName)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findConversationsByClientId(int $clientId)
    {
        return $this->createQueryBuilder('m')
            ->where('m.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadMessagesByClientId(int $clientId): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.client = :clientId')
            ->andWhere('m.isFromClient = false')
            ->andWhere('m.isRead = false')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadMessagesByClientName(string $clientName): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.nomAuteur = :nom')
            ->andWhere('m.isFromClient = false')
            ->andWhere('m.isRead = false')
            ->setParameter('nom', $clientName)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUniqueClientNames()
    {
        $results = $this->createQueryBuilder('m')
            ->select('DISTINCT m.nomAuteur')
            ->where('m.isFromClient = true')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return array_map(function($item) {
            return $item['nomAuteur'];
        }, $results);
    }
}
