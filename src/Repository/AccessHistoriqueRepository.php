<?php

namespace App\Repository;

use App\Entity\AccessHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessHistorique>
 *
 * @method AccessHistorique|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccessHistorique|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccessHistorique[]    findAll()
 * @method AccessHistorique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccessHistoriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessHistorique::class);
    }

//    /**
//     * @return AccessHistorique[] Returns an array of AccessHistorique objects
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

//    public function findOneBySomeField($value): ?AccessHistorique
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findAccessByDate($dateDebut, $dateFin): array
   {
       return $this->createQueryBuilder('a')
            ->innerJoin("a.User", "user")
            ->where('a.dateAccess >= :dateDebut')
            ->andWhere('a.dateAccess <= :dateFin')
            ->setParameter('dateDebut', $dateDebut.' 00:00:00')
            ->setParameter('dateFin', $dateFin.' 23:59:59')
            ->getQuery()
            ->getResult()
       ;
   }
}
