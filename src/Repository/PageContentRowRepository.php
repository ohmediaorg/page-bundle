<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\PageContentRow;

/**
 * @extends ServiceEntityRepository<PageContentRow>
 *
 * @method PageContentRow|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageContentRow|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageContentRow[]    findAll()
 * @method PageContentRow[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageContentRowRepository extends AbstractPageContentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageContentRow::class);
    }
}
