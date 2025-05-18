<?php

namespace App\Repository;

use App\Entity\ArticleCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleCommande>
 */
class ArticleCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleCommande::class);
    }
    public function findBestSellingBook(\DateTimeInterface $from, \DateTimeInterface $to): ?array
    {
        $qb = $this->createQueryBuilder('ac')
            ->select('IDENTITY(ac.livre) AS livreId, l.titre AS titre, l.image AS image, l.prix AS prix, 
                     SUM(ac.quantite) AS totalVendu, SUM(ac.prix * ac.quantite) AS chiffreAffaire')
            ->join('ac.livre', 'l')
            ->join('ac.commande', 'c')
            ->where('c.dateCommande BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('ac.livre')
            ->orderBy('totalVendu', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findTopSellingBooks(\DateTimeInterface $from, \DateTimeInterface $to, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('ac')
            ->select('IDENTITY(ac.livre) AS livreId, l.titre AS titre, l.image AS image, 
                     SUM(ac.quantite) AS totalVendu, SUM(ac.prix * ac.quantite) AS chiffreAffaire')
            ->join('ac.livre', 'l')
            ->join('ac.commande', 'c')
            ->where('c.dateCommande BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('ac.livre')
            ->orderBy('totalVendu', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }


    //    /**
    //     * @return ArticleCommande[] Returns an array of ArticleCommande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ArticleCommande
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
