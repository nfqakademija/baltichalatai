<?php

namespace App\Repository;

use App\Entity\UserVisit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserVisit|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserVisit|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserVisit[]    findAll()
 * @method UserVisit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserVisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserVisit::class);
    }

    public function findBySpecialistId($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.specialistId = :val')
            ->setParameter('val', $value)
            ->orderBy('u.visitDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $value
     * @return Array
     */
    public function findByPatientIdNotCompleted($value): Array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.clientId = :val')
            ->andWhere('u.isCompleted = 0')
            ->setParameter('val', $value)
            ->orderBy('u.visitDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $value
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getWithPatientIdCompletedQueryBuilder($value): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->createQueryBuilder('u')
            ->andWhere('u.clientId = :val')
            ->andWhere('u.isCompleted = 1')
            ->setParameter('val', $value)
            ->orderBy('u.visitDate', 'DESC');

        return $query;
    }

    /**
     * @param $value
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getWithSpecialistIdQueryBuilder($value): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->createQueryBuilder('u')
            ->andWhere('u.specialistId = :val')
            ->andWhere('u.isCompleted = 0')
            ->setParameter('val', $value)
            ->orderBy('u.visitDate', 'ASC');

        return $query;
    }

    /**
     * @param int $specialistId
     * @return mixed
     */
    public function findSpecialistVisits(int $specialistId)
    {
        return $this->createQueryBuilder('v')
            ->where('v.specialistId = :specialistId')
            ->setParameter('specialistId', $specialistId)
            ->orderBy('v.visitDate', 'desc')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $date
     * @param $specialistId
     * @param $clinicId
     * @return mixed
     */
    public function checkIfWorkHourExists($date, int $specialistId, int $clinicId)
    {
        return $this->getEntityManager()->getRepository(UserVisit::class)
            ->findBy([
                'specialistId' => $specialistId,
                'clinicId' => $clinicId,
                'visitDate' => $date,
            ]);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getVisitById(int $id)
    {
        return $this->getEntityManager()->getRepository(UserVisit::class)->find($id);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getVisitsWithRecipesByClientId(int $id)
    {
        return $this->createQueryBuilder('u')
            ->where('u.clientId = :client')
            ->andWhere('u.recipeId is not null')
            ->setParameter('client', $id)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getVisitsWithSendingsByClientId(int $id)
    {
        return $this->createQueryBuilder('u')
            ->where('u.clientId = :client')
            ->andWhere('u.sendingToDoctorId is not null')
            ->setParameter('client', $id)
            ->getQuery()
            ->getResult();
    }
}
