<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }
    public function countOrdersPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select(
                'YEAR(c.dateCommande)   AS year',
                'MONTH(c.dateCommande)  AS month',
                'DAY(c.dateCommande)    AS day',
                'COUNT(c.id)            AS nbCommandes',
                'SUM(c.montantTotal)    AS totalRevenue'
            )
            ->where('c.dateCommande BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to',   $to)
            ->groupBy('year, month, day')
            ->orderBy('year, month, day', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function getOrderStatsByPeriod(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select(
                'COUNT(c.id) AS totalOrders',
                'SUM(c.montantTotal) AS totalRevenue',
                'AVG(c.montantTotal) AS averageOrderValue'
            )
            ->where('c.dateCommande BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return $qb->getQuery()->getOneOrNullResult();
    }



    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
