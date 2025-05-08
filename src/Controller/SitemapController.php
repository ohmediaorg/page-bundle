<?php

namespace OHMedia\PageBundle\Controller;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\PageBundle\Sitemap\AbstractSitemapUrlProvider;
use OHMedia\PageBundle\Sitemap\SitemapUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    private array $sitemapUrlProviders = [];

    public function addSitemapUrlProvider(AbstractSitemapUrlProvider $sitemapUrlProvider): void
    {
        $this->sitemapUrlProviders[] = $sitemapUrlProvider;
    }

    #[Route('/sitemap.xml', name: 'oh_media_page_sitemap')]
    public function sitemap(PageRepository $pageRepository): Response
    {
        $pages = $pageRepository->createQueryBuilder('p')
            ->where('(p.homepage = 1 OR p.sitemap = 1)')
            ->andWhere('IDENTITY(p.canonical) IS NULL')
            ->andWhere('(p.redirect_type = :redirect_type_none OR p.redirect_type IS NULL)')
            ->setParameter('redirect_type_none', Page::REDIRECT_TYPE_NONE)
            ->andWhere('p.noindex = 0')
            ->andWhere('p.locked = 0')
            ->orderBy('p.order_global', 'asc')
            ->getQuery()
            ->getResult();

        $sitemapUrls = [];

        foreach ($pages as $page) {
            if (!$page->isVisibleToPublic()) {
                continue;
            }

            if ($page->getDropdownOnlyRedirect()) {
                continue;
            }

            $pageRevision = $page->getCurrentPageRevision();

            $path = $page->isHomepage() ? '' : $page->getPath();

            $sitemapUrls[] = new SitemapUrl($path, $pageRevision->getUpdatedAt());
        }

        foreach ($this->sitemapUrlProviders as $sitemapUrlProvider) {
            $sitemapUrls = array_merge($sitemapUrls, $sitemapUrlProvider->getSitemapUrls());
        }

        $response = $this->render('@OHMediaPage/sitemap.xml.twig', [
            'sitemap_urls' => $sitemapUrls,
        ]);

        $response->headers->set('Content-Type', 'xml');

        return $response;
    }
}
