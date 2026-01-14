<?php

namespace OHMedia\PageBundle\Service;

use Doctrine\ORM\QueryBuilder;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\TimezoneBundle\Util\DateTimeUtil;
use OHMedia\UtilityBundle\Service\AbstractEntityPathProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageEntityPathProvider extends AbstractEntityPathProvider
{
    public function __construct(
        private PageRepository $pageRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getEntityClass(): string
    {
        return Page::class;
    }

    public function getGroupLabel(): string
    {
        return 'Pages';
    }

    public function getEntityQueryBuilder(?int $selectedEntityId): QueryBuilder
    {
        $where = [
            'p.homepage = 0',
            'p.locked = 0',
            'p.published IS NOT NULL',
            'p.published <= :now',
        ];

        $qb = $this->pageRepository
            ->createQueryBuilder('p')
            ->where('('.implode(' AND ', $where).')')
            ->setParameter('now', DateTimeUtil::getDateTimeUtc())
            ->orderBy('p.order_global', 'ASC');

        if ($selectedEntityId) {
            $qb->orWhere('p.id = :id')
                ->setParameter('id', $selectedEntityId);
        }

        return $qb;
    }

    public function getEntityPath(mixed $entity): ?string
    {
        if (!$entity->isPublished()) {
            return null;
        }

        return $this->urlGenerator->generate('oh_media_page_frontend', [
            'path' => $entity->getPath(),
        ]);
    }

    public function getEntityLabel(mixed $entity): string
    {
        return str_repeat('- ', $entity->getNestingLevel()).$entity;
    }
}
