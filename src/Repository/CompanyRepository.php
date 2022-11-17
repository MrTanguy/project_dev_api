<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Company>
 *
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function save(Company $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Company $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Company[] Returns an array of Company objects
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

//    public function findOneBySomeField($value): ?Company
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findWithPagination($page, $limit)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);
        $qb->where('s.status = \'on\'');
        return $qb->getQuery()->getResult(); 
    }

    /**
     * @return Company[] Returns an array of Company objects
     */
    public function findNearestCompanyByJob($lat, $lon, $job, $limit)
    {       
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Company::class, 'company');
        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT * 
            FROM `company` 
            WHERE company.status = "on" AND company.job = :job
            ORDER BY (6378 * acos(cos(radians(:latitude)) * cos(radians(company.lat)) * cos(radians(company.lon) - radians(:longitude)) + sin(radians(:latitude)) * sin(radians(company.lat))))
            LIMIT :limitValue',
            $rsm
        );
        $query->setParameter('latitude', $lat);
        $query->setParameter('longitude', $lon);
        $query->setParameter('job', $job);
        $query->setParameter('limitValue', $limit);
        return $query->getResult();
    }
}
