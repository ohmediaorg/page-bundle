<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\PageContentImage;

/**
 * @extends ServiceEntityRepository<PageContentImage>
 *
 * @method PageContentImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageContentImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageContentImage[]    findAll()
 * @method PageContentImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageContentImageRepository extends AbstractPageContentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageContentImage::class);
    }
}
