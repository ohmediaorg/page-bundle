<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\PageContentCheckbox;

/**
 * @extends ServiceEntityRepository<PageContentCheckbox>
 *
 * @method PageContentCheckbox|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageContentCheckbox|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageContentCheckbox[]    findAll()
 * @method PageContentCheckbox[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageContentCheckboxRepository extends AbstractPageContentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageContentCheckbox::class);
    }

    protected function alterExistsQueryBuilder(QueryBuilder $qb): void
    {
        $qb->andWhere('c.checked = 1');
    }
}
