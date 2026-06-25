<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OHMedia\PageBundle\Entity\Redirect;

/**
 * @method Redirect|null find($id, $lockMode = null, $lockVersion = null)
 * @method Redirect|null findOneBy(array $criteria, array $orderBy = null)
 * @method Redirect[]    findAll()
 * @method Redirect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Redirect::class);
    }

    public function save(Redirect $redirect, bool $flush = false): void
    {
        $this->getEntityManager()->persist($redirect);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Redirect $redirect, bool $flush = false): void
    {
        $this->getEntityManager()->remove($redirect);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
