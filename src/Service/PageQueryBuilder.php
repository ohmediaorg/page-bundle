<?php

namespace OHMedia\PageBundle\Service;

use Doctrine\ORM\QueryBuilder;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;

class PageQueryBuilder
{
    private ?string $alias;
    private PageRepository $pageRepository;
    private ?queryBuilder $queryBuilder;

    private ?Page $exclude;
    private ?bool $homepage;
    private ?bool $locked;
    private ?bool $published;

    public function __construct(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    public function createQueryBuilder(string $alias = 'p'): self
    {
        $this->alias = $alias;
        $this->queryBuilder = $this->pageRepository->createQueryBuilder($alias);

        $this->exclude = null;
        $this->homepage = null;
        $this->locked = null;
        $this->published = null;

        return $this;
    }

    public function exclude(?Page $exclude): self
    {
        $this->exclude = $exclude;

        return $this;
    }

    public function homepage(?bool $homepage): self
    {
        $this->homepage = $homepage;

        return $this;
    }

    public function locked(?bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function published(?bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        $this->applyExclude();
        $this->applyHomepage();
        $this->applyLocked();
        $this->applyPublished();

        return $this->queryBuilder;
    }

    private function applyExclude(): void
    {
        if (null === $this->exclude) {
            return;
        }

        $field = $this->alias.'.id';

        $this->queryBuilder
            ->andWhere("$field <> :excludePage")
            ->setParameter('excludePage', $this->exclude->getId())
        ;
    }

    private function applyHomepage(): void
    {
        if (null === $this->homepage) {
            return;
        }

        $field = $this->alias.'.homepage';

        if ($this->homepage) {
            $this->queryBuilder->andWhere("$field = 1");
        } else {
            $this->queryBuilder->andWhere("$field = 0");
        }
    }

    private function applyLocked(): void
    {
        if (null === $this->locked) {
            return;
        }

        $field = $this->alias.'.locked';

        if ($this->locked) {
            $this->queryBuilder->andWhere("$field = 1");
        } else {
            $this->queryBuilder->andWhere("$field = 0");
        }
    }

    private function applyPublished(): void
    {
        if (null === $this->published) {
            return;
        }

        $field = $this->alias.'.published';

        if ($this->published) {
            $this->queryBuilder
                ->andWhere("$field IS NOT NULL")
                ->andWhere("$field >= :publishedSince")
                ->setParameter('publishedSince', new \DateTime())
            ;
        } else {
            $this->queryBuilder
                ->andWhere("($field IS NULL OR $field < :publishedSince)")
                ->setParameter('publishedSince', new \DateTime())
            ;
        }
    }
}
