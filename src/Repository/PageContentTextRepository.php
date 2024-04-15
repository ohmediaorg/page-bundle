<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\PageContentText;

/**
 * @extends ServiceEntityRepository<PageContentText>
 *
 * @method PageContentText|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageContentText|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageContentText[]    findAll()
 * @method PageContentText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageContentTextRepository extends AbstractPageContentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageContentText::class);
    }

    protected function alterExistsQueryBuilder(QueryBuilder $qb): void
    {
        $qb
            ->andWhere('c.text IS NOT NULL')
            ->andWhere("c.text <> ''")
        ;
    }
}
