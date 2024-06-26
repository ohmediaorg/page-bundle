<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\Page301;

/**
 * @extends ServiceEntityRepository<Page301>
 *
 * @method Page301|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page301|null findOneBy(array $criteria, array $orderBy = null)
 * @method Page301[]    findAll()
 * @method Page301[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Page301Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page301::class);
    }

    public function save(Page301 $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Page301 $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
