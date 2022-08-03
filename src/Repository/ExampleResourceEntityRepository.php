<?php

namespace App\Repository;

use App\Entity\ExampleResourceEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExampleResourceEntity>
 *
 * @method ExampleResourceEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExampleResourceEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExampleResourceEntity[]    findAll()
 * @method ExampleResourceEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExampleResourceEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExampleResourceEntity::class);
    }

    public function add(ExampleResourceEntity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ExampleResourceEntity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ExampleResourceEntity[] Returns an array of ExampleResourceEntity objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ExampleResourceEntity
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
