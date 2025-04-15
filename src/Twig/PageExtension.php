<?php

namespace OHMedia\PageBundle\Twig;

use OHMedia\BootstrapBundle\Component\Breadcrumb;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\PageBundle\Service\PageRenderer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageExtension extends AbstractExtension
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private PageRepository $pageRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
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
        ];
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
