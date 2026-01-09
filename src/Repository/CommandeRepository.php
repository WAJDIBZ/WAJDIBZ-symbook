<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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
        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform()->getName();

        // Column names follow the DB schema (snake_case)
        $dateCol = 'date_commande';
        $amountCol = 'montant_total';
        $table = 'commande';

        switch ($platform) {
            case 'sqlite':
                $yearExpr = "CAST(strftime('%Y', $dateCol) AS INTEGER)";
                $monthExpr = "CAST(strftime('%m', $dateCol) AS INTEGER)";
                $dayExpr = "CAST(strftime('%d', $dateCol) AS INTEGER)";
                break;
            case 'mysql':
            case 'mariadb':
                $yearExpr = "YEAR($dateCol)";
                $monthExpr = "MONTH($dateCol)";
                $dayExpr = "DAY($dateCol)";
                break;
            default:
                // e.g. PostgreSQL
                $yearExpr = "EXTRACT(YEAR FROM $dateCol)";
                $monthExpr = "EXTRACT(MONTH FROM $dateCol)";
                $dayExpr = "EXTRACT(DAY FROM $dateCol)";
                break;
        }

        $sql = "
            SELECT
                $yearExpr  AS year,
                $monthExpr AS month,
                $dayExpr   AS day,
                COUNT(id)  AS nbCommandes,
                SUM($amountCol) AS totalRevenue
            FROM $table
            WHERE $dateCol BETWEEN :from AND :to
            GROUP BY $yearExpr, $monthExpr, $dayExpr
            ORDER BY $yearExpr ASC, $monthExpr ASC, $dayExpr ASC
        ";

        return $conn->fetchAllAssociative(
            $sql,
            ['from' => $from, 'to' => $to],
            ['from' => Types::DATETIME_MUTABLE, 'to' => Types::DATETIME_MUTABLE]
        );
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
