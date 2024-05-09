<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\BackendBundle\ContentLinks\AbstractContentLinkProvider;
use OHMedia\BackendBundle\ContentLinks\ContentLink;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;

class PageContentLinkProvider extends AbstractContentLinkProvider
{
    public function __construct(private PageRepository $pageRepository)
    {
    }

    public function getTitle(): string
    {
        return 'Pages';
    }

    public function buildContentLinks(): void
    {
        $contentLinks = $this->createContentLinks();

        foreach ($contentLinks as $contentLink) {
            $this->addContentLink($contentLink);
        }
    }

    private function createContentLinks(Page $parent = null): array
    {
        $queryBuilder = $this->pageRepository->createQueryBuilder('p');

        if ($parent) {
            $queryBuilder->where('p.parent = :parent');
            $queryBuilder->setParameter('parent', $parent);
        } else {
            $queryBuilder->where('p.parent IS NULL');
        }

        $pages = $queryBuilder
            ->orderBy('p.order_local')
            ->getQuery()
            ->getResult();

        $contentLinks = [];

        foreach ($pages as $page) {
            $id = $page->getId();

            $title = sprintf('%s (ID:%s)', $page, $id);

            $contentLink = new ContentLink($title);
            $contentLink->setShortcode('page_href('.$id.')');

            $contentLinks[] = $contentLink;

            $children = $this->createContentLinks($page);

            if ($children) {
                $contentLink = new ContentLink((string) $page.' (Child Pages)');
                $contentLink->setChildren(...$children);

                $contentLinks[] = $contentLink;
            }
        }

        return $contentLinks;
    }
}
