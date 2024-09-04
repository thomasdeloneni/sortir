<?php

namespace App\Repository;

use App\Entity\Sortie;
use App\Form\model\SortieSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;


/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    private $security;
    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Sortie::class);
        $this->security = $security;
    }

    public function findByFilters(SortieSearch $search)
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->leftJoin('s.etat', 'e')
            ->addSelect('e');

        if ($search->getNom()) {
            $queryBuilder->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $search->getNom() . '%');
        }

        if ($search->getStartDate()) {
            $queryBuilder->andWhere('s.dateHeureDebut >= :startDate')
                ->setParameter('startDate', $search->getStartDate());
        }

        if ($search->getEndDate()) {
            $queryBuilder->andWhere('s.dateHeureDebut <= :endDate')
                ->setParameter('endDate', $search->getEndDate());
        }

        if ($search->getCampus()) {
            $queryBuilder->andWhere('s.campus = :campus')
                ->setParameter('campus', $search->getCampus());
        }

        if ($search->getIsOrganizer()) {
            $user = $this->security->getUser();
            $queryBuilder->andWhere('s.organisateur = :organisateur')
                ->setParameter('organisateur', $user);
        }

        if ($search->getIsInscrit()) {
            $user = $this->security->getUser();
            $queryBuilder->andWhere(':participant MEMBER OF s.participant')
                ->setParameter('participant', $user);
        }

        if ($search->getIsNotInscrit()) {
            $user = $this->security->getUser();
            $queryBuilder->andWhere(':participant NOT MEMBER OF s.participant')
                ->setParameter('participant', $user);
        }

        if ($search->getIsFinished()) {
            $queryBuilder->andWhere('e.libelle = :etat')
                ->setParameter('etat', 'Passée');
        }

        return $queryBuilder->getQuery()->getResult();
    }


    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
