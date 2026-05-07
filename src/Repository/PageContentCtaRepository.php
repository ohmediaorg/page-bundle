<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\PageContentCta;

/**
 * @extends ServiceEntityRepository<PageContentCta>
 *
 * @method PageContentCta|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageContentCta|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageContentCta[]    findAll()
 * @method PageContentCta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageContentCtaRepository extends AbstractPageContentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageContentCta::class);
    }
}
