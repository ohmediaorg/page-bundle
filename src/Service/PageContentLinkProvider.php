<?php

namespace OHMedia\PageBundle\Service;

use OHMedia\BackendBundle\ContentLinks\AbstractContentLinkProvider;
use OHMedia\BackendBundle\ContentLinks\ContentLink;
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
        $pages = $this->pageRepository->createQueryBuilder('p')
            ->orderBy('p.order_global')
            ->getQuery()
            ->getResult();

        foreach ($pages as $page) {
            if (!$page->isPublished()) {
                continue;
            }

            $id = $page->getId();

            $prefix = str_repeat('- ', $page->getNestingLevel());

            $contentLink = new ContentLink(
                sprintf('%s%s (ID:%s)', $prefix, $page, $id),
                (string) $page
            );
            $contentLink->setShortcode('page_href('.$id.')');

            $this->addContentLink($contentLink);
        }
    }
}
