<?php

namespace OHMedia\PageBundle\Twig;

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
            new TwigFunction('page_meta', [$this, 'pageMeta'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
            new TwigFunction('page_nav', [$this, 'pageNav'], [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function pageMeta(Environment $twig)
    {
        $page = $this->pageRenderer->getCurrentPage();

        if (!$page) {
            return;
        }

        $meta = $this->pageRenderer->getMetaEntity();

        return $twig->render('@OHMediaPage/meta.html.twig', [
            'page' => $page,
            'meta' => $meta,
        ]);
    }

    public function pageNav(Environment $twig, bool $showHome = true, int $maxNestingLevel = 2)
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
}
