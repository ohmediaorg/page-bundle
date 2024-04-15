<?php

namespace OHMedia\PageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use OHMedia\PageBundle\Entity\PageRevision;

abstract class AbstractPageContentRepository extends ServiceEntityRepository
{
    final public function baseQueryBuilder(
        string $alias,
        ?PageRevision $pageRevision,
        string $name
    ): QueryBuilder {
        return $this->createQueryBuilder($alias)
            ->where($alias.'.pageRevision = :pageRevision')
            ->setParameter('pageRevision', $pageRevision)
            ->andWhere($alias.'.name = :name')
            ->setParameter('name', $name);
    }
}
