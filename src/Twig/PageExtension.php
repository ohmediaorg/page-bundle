<?php

namespace OHMedia\PageBundle\Twig;

use OHMedia\BootstrapBundle\Component\Breadcrumb;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\PageBundle\Service\PageRenderer;
use OHMedia\TimezoneBundle\Util\DateTimeUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageExtension extends AbstractExtension
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private PageRepository $pageRepository,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('page_path', [$this, 'path']),
            new TwigFunction('page_breadcrumbs', [$this, 'breadcrumbs'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('page_meta', [$this, 'meta'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('page_nav', [$this, 'nav'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('get_page_nav', [$this, 'getPageNav']),
        ];
    }

    public function path(string $path): string
    {
        return $this->urlGenerator->generate('oh_media_page_frontend', [
            'path' => $path,
        ]);
    }

    public function breadcrumbs(Environment $twig)
    {
        $page = $this->pageRenderer->getCurrentPage();

        if (!$page) {
            return;
        }

        return $twig->render('@OHMediaPage/breadcrumbs.html.twig', [
            'breadcrumbs' => $this->getBreadcrumbs($page, false),
        ]);
    }

    public function meta(Environment $twig)
    {
        $page = $this->pageRenderer->getCurrentPage();

        if (!$page) {
            return;
        }

        $meta = $this->pageRenderer->getMetaEntity();

        $canonicalPath = $this->pageRenderer->getCanonicalPath();

        $breadcrumbs = $this->getBreadcrumbs($page, true);

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        foreach ($breadcrumbs as $i => $breadcrumb) {
            $breadcrumbSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $breadcrumb->getText(),
                'item' => $this->urlGenerator->generate(
                    $breadcrumb->getRoute(),
                    $breadcrumb->getRouteParams(),
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ];
        }

        return $twig->render('@OHMediaPage/meta.html.twig', [
            'page' => $page,
            'meta' => $meta,
            'canonical_path' => $canonicalPath,
            'breadcrumb_schema' => $breadcrumbSchema,
        ]);
    }

    public function nav(Environment $twig, string $className = 'nav', int $maxNestingLevel = 1)
    {
        // NOTE: Bootstrap only supports 1 level of dropdown out of the box
        // still figuring out what we want to do with the navigation
        $maxNestingLevel = 1;

        if ($maxNestingLevel < 0) {
            $maxNestingLevel = 0;
        }

        $nav = $this->getPageNav($maxNestingLevel);

        return $twig->render('@OHMediaPage/nav.html.twig', [
            'nav' => $nav,
            'class_name' => $className,
        ]);
    }

    public function getPageNav(int $maxNestingLevel): array
    {
        if ($maxNestingLevel < 0) {
            $maxNestingLevel = 0;
        }

        $navPages = $this->getNavPages($maxNestingLevel);

        return $this->getNav($navPages);
    }

    private function getNavPages(int $maxNestingLevel): array
    {
        return $this->pageRepository->createQueryBuilder('p')
            ->where('p.hidden = 0')
            ->andWhere('p.nesting_level <= :max_nesting_level')
            ->setParameter('max_nesting_level', $maxNestingLevel)
            ->andWhere('p.published IS NOT NULL')
            ->andWhere('p.published <= :now')
            ->setParameter('now', DateTimeUtil::getDateTimeUtc())
            ->andWhere('(
                SELECT COUNT(pr)
                FROM OHMedia\PageBundle\Entity\PageRevision pr
                WHERE IDENTITY(pr.page) = p.id
                AND pr.published = 1
            ) > 0')
            ->orderBy('p.order_global', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getNav(array $navPages, ?Page $parent = null): array
    {
        $nav = [];

        foreach ($navPages as $page) {
            if ($page->getParent() !== $parent) {
                continue;
            }

            $nav[] = [
                'page' => $page,
                'children' => $this->getNav($navPages, $page),
            ];
        }

        return $nav;
    }

    private function getBreadcrumbs(Page $page, bool $schema): array
    {
        if ($page->isHomepage()) {
            return [$this->getHomepageBreadcrumb()];
        }

        $breadcrumbs = $this->pageRenderer->getDynamicBreadcrumbs();

        $curr = $page;

        do {
            if ($schema) {
                $meta = $curr->getMeta();

                $text = $meta && $meta->getTitle() ? $meta->getTitle() : $curr->getName();
            } else {
                $text = $curr->getNavText() ?: $curr->getName();
            }

            array_unshift($breadcrumbs, $this->getBreadcrumb($text, $curr->getPath()));
        } while ($curr = $curr->getParent());

        array_unshift($breadcrumbs, $this->getHomepageBreadcrumb());

        return $breadcrumbs;
    }

    private function getHomepageBreadcrumb(): Breadcrumb
    {
        $homepage = $this->pageRepository->getHomepage();

        $text = $homepage && $homepage->getNavText()
            ? $homepage->getNavText()
            : 'Home';

        return $this->getBreadcrumb($text, '');
    }

    private function getBreadcrumb(string $text, string $path): Breadcrumb
    {
        return new Breadcrumb(
            $text,
            'oh_media_page_frontend',
            ['path' => $path]
        );
    }
}
