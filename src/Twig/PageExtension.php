<?php

namespace OHMedia\PageBundle\Twig;

use OHMedia\BootstrapBundle\Component\Breadcrumb;
use OHMedia\MetaBundle\Entity\Meta;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\PageBundle\Service\PageRenderer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageExtension extends AbstractExtension
{
    private $pageRenderer;
    private $pageRepository;
    private $requestStack;
    private $urlGenerator;

    public function __construct(
        PageRenderer $pageRenderer,
        PageRepository $pageRepository,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->pageRenderer = $pageRenderer;
        $this->pageRepository = $pageRepository;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions(): array
    {
        return [
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
        ];
    }

    public function breadcrumbs(Environment $twig)
    {
        $page = $this->pageRenderer->getCurrentPage();

        if (!$page) {
            return;
        }

        return $twig->render('@OHMediaPage/breadcrumbs.html.twig', [
            'breadcrumbs' => $this->getBreadcrumbs($page),
        ]);
    }

    public function meta(Environment $twig)
    {
        $page = $this->pageRenderer->getCurrentPage();

        if (!$page) {
            return;
        }

        $meta = $this->pageRenderer->getMetaEntity();

        $breadcrumbs = $this->getBreadcrumbs($page);

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
            'breadcrumb_schema' => $breadcrumbSchema,
        ]);
    }

    public function nav(Environment $twig, bool $showHome = true, int $maxNestingLevel = 2)
    {
        if ($maxNestingLevel < 0) {
            $maxNestingLevel = 0;
        }

        $topLevelPages = $this->pageRepository->getTopLevel();

        // NOTE: we could do the isNavEligible checks in a query
        // but that would duplicate the logic
        $pages = array_filter($topLevelPages, function (Page $page) {
            return $page->isNavEligible();
        });

        $homePath = $this->urlGenerator->generate('oh_media_page_frontend', [
            'path' => '',
        ]);

        $currentPath = $this->requestStack->getCurrentRequest()->getPathInfo();

        return $twig->render('@OHMediaPage/nav.html.twig', [
            'pages' => $pages,
            'home_path' => $homePath,
            'show_home' => $showHome,
            'current_path' => $currentPath,
            'max_nesting_level' => $maxNestingLevel,
        ]);
    }

    private function getBreadcrumbs(Page $page)
    {
        $meta = $this->pageRenderer->getMetaEntity();

        $breadcrumbs = [];

        if (!$page->isHomepage()) {
            $breadcrumbs[] = $this->getBreadcrumb($page, $meta);
        }

        $curr = $page;

        while ($curr = $curr->getParent()) {
            $meta = $curr->getMeta();

            array_unshift($breadcrumbs, $this->getBreadcrumb($curr, $meta));
        }

        array_unshift($breadcrumbs, new Breadcrumb(
            'Home',
            'oh_media_page_frontend',
            ['path' => '']
        ));

        return $breadcrumbs;
    }

    private function getBreadcrumb(Page $page, Meta $meta)
    {
        return new Breadcrumb(
            $meta->getTitle() ?? $page->getName(),
            'oh_media_page_frontend',
            ['path' => $page->isHomepage() ? '' : $page->getPath()]
        );
    }
}
