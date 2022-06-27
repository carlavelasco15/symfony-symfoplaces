<?php

namespace App\Repository;

use App\Entity\Pelicula;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pelicula>
 *
 * @method Pelicula|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pelicula|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pelicula[]    findAll()
 * @method Pelicula[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PeliculaRepository extends ServiceEntityRepository
{


    public function findAllByDuration(int $min, int $max):array {

        return $this->getEntityManager()->createQuery(
            "SELECT p
            FROM App\Entity\Pelicula p
            WHERE p.duracion BETWEEN :min AND :max
            ORDER BY p.duracion ASC"
        )
        ->setParameter("min", $min)
        ->setParameter("max", $max)
        ->getResult();
    }


    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pelicula::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Pelicula $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Pelicula $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function campos() {

        return $this->getEntityManager()->createQuery(
            "SELECT partial p.{id, titulo, estreno}
            FROM App\Entity\Pelicula p"
        )
        ->getResult();
    }

    // /**
    //  * @return Pelicula[] Returns an array of Pelicula objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Pelicula
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    

    public function findLast(int $quantity=5, bool $cover= true):array {

        return $this->getEntityManager()->createQuery(
            "SELECT p
            FROM App\Entity\Pelicula p"
            . ($cover? ' WHERE p.image IS NOT NULL': '')
           ." ORDER BY p.id DESC"
        )
        ->setMaxResults($quantity)
        ->getResult();
    }



    
}
