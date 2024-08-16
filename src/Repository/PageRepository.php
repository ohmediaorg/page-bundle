<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\Page;

/**
 * @method Page|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null findOneBy(array $criteria, array $orderBy = null)
 * @method Page[]    findAll()
 * @method Page[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function save(Page $page, bool $flush = false): void
    {
        $this->getEntityManager()->persist($page);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Page $page, bool $flush = false): void
    {
        $this->getEntityManager()->remove($page);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function countBySlug(string $slug, ?int $id = null)
    {
        $params = [
            new Parameter('slug', $slug),
        ];

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.slug = :slug');

        if ($id) {
            $qb->andWhere('p.id <> :id');

            $params[] = new Parameter('id', $id);
        }

        return $qb->setParameters(new ArrayCollection($params))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTopLevel()
    {
        return $this->createQueryBuilder('p')
            ->where('p.parent IS NULL')
            ->andWhere('p.homepage = 0')
            ->orderBy('p.order_local')
            ->getQuery()
            ->getResult();
    }

    public function getHomepage(): ?Page
    {
        return $this->createQueryBuilder('p')
            ->where('p.homepage = 1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
