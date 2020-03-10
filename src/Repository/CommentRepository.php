<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;

    public const PAGINATOR_PER_PAGE = 2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function getCommentPaginator(Conference $conference, int $offset, string $state = 'published'): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.conference = :conference')
            ->setParameter('conference', $conference)
            ->andWhere('c.state = :state')
            ->setParameter('state', $state)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery();

        return new Paginator($query);
    }

    public function countOldRejectedComments(): int
    {
        return $this->getQueryBuilderForOldRejectedComments()
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldRejectedComments(): int
    {
        return $this->getQueryBuilderForOldRejectedComments()
            ->delete()
            ->getQuery()
            ->execute();
    }

    private function getQueryBuilderForOldRejectedComments(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere("c.state = 'rejected' or c.state = 'spam'")
            ->andWhere('c.createdAt < :date')
            ->setParameter('date', new DateTime(-self::DAYS_BEFORE_REJECTED_REMOVAL.' days'));
    }
}
