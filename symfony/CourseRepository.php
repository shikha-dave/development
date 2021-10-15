<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CourseRepository
 *
 * @method Course|null find(mixed $id, $lockMode = null, $lockVersion = null) @phan-suppress-current-line PhanParamSignaturePHPDocMismatchHasNoParamType
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) @phan-suppress-current-line PhanParamSignaturePHPDocMismatchHasNoParamType
 */
class CourseRepository extends AbstractRepository
{
    /**
     * CourseRepository constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Course::class);
    }

    /**
     * @return Course[]
     */
    public function getAllForList(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()->execute();
    }

    /**
     * @return Course[]
     */
    public function getFutureCourses(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.startDate > :NOW')
            ->andWhere('c.fullyBooked <> 1')
            ->setParameter('NOW', new \DateTime())
            ->orderBy('c.startDate', 'ASC')
            ->getQuery()->execute();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getWaitlistStatistics(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('c')
            ->select('count(c.id) as count')
            ->andWhere('c.startDate <= :END')
            ->andWhere('c.endDate >= :START')
            ->setParameter('START', $start)
            ->setParameter('END', $end)
            ->getQuery()->getSingleResult();
    }

    /**
     * @return Course[]
     *
     * @throws \Exception
     */
    public function getSartingCourses(): array
    {
        $date = new \DateTime();
        $from = new \DateTime($date->format('Y-m-d').' 00:00:00');
        $to   = new \DateTime($date->format('Y-m-d').' 23:59:59');

        return $this->createQueryBuilder('c')
            ->andWhere('c.startDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->execute();
    }

    /**
     * @return Course[]
     *
     * @throws \Exception
     */
    public function getEndedCourses(): array
    {
        $to = new \DateTime('-1 day');
        $from = new \DateTime('-2 month');

        return $this->createQueryBuilder('c')
            ->andWhere('c.endDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->execute();
    }

    /**
     * @param string $days
     *
     * @return Course[]
     */
    public function getCourseStartList(string $days): array
    {
        $date = new \DateTime();
        $date->modify('+'.$days.' days');

        return $this->createQueryBuilder('c')
            ->where('c.startDate < :startDate')
            ->andWhere('c.startDate > :now')
            ->andWhere('c.reminderSent = 0')
            ->setParameter('startDate', $date)
            ->setParameter('now', new \DateTime())
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()->execute();
    }

    /**
     * @param string $days
     *
     * @return array
     */
    public function getCourseEndList(string $days): array
    {
        $date = new \DateTime();
        $date->modify('+'.$days.' days');

        return $this->createQueryBuilder('c')
            ->where('c.endDate < :endDate')
            ->andWhere('c.endDate > :now')
            ->andWhere('c.surveyEndSent = 0')
            ->setParameter('endDate', $date)
            ->setParameter('now', new \DateTime())
            ->orderBy('c.endDate', 'DESC')
            ->getQuery()->execute();
    }

    /**
     * @param string $days
     *
     * @return array
     */
    public function getCourseStartForSurveyList(string $days): array
    {
        $date = new \DateTime();
        $date->modify('+'.$days.' days');

        return $this->createQueryBuilder('c')
            ->where('c.startDate < :startDate')
            ->andWhere('c.startDate > :now')
            ->andWhere('c.surveyStartSent = 0')
            ->setParameter('startDate', $date)
            ->setParameter('now', new \DateTime())
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()->execute();
    }
}
