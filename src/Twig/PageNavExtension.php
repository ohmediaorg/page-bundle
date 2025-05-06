<?php

namespace OHMedia\PageBundle\Twig;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\PageBundle\Security\Voter\PageLockedVoter;
use OHMedia\TimezoneBundle\Util\DateTimeUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageNavExtension extends AbstractExtension
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private PageRepository $pageRepository,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('page_path', [$this, 'path']),
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

    public function getPageNav(int $maxNestingLevel = 1): array
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

        $request = $this->requestStack->getCurrentRequest();

        $currentPath = $request->getBaseUrl().$request->getPathInfo();

        foreach ($navPages as $page) {
            if ($page->getParent() !== $parent) {
                continue;
            }

            if (!$this->authorizationChecker->isGranted(PageLockedVoter::LOCKED, $page)) {
                continue;
            }

            if ($page->isHomepage()) {
                $href = $this->path('');
            } elseif ($page->isRedirectTypeInternal()) {
                $href = $this->path($page->getRedirectInternal()->getPath());
            } elseif ($page->isRedirectTypeExternal()) {
                $href = $page->getRedirectExternal();
            } else {
                $href = $this->path($page->getPath());
            }

            $text = $page->getNavText() ?: $page->getName();
            $dropdownText = $page->getDropdownText() ?: $page->getName();

            $active = $href === $currentPath;
            $activePath = str_starts_with($currentPath, $href);

            $nav[] = [
                'page' => $page,
                'href' => $href,
                'text' => $text,
                'dropdown_text' => $dropdownText,
                'active' => $active,
                'active_path' => $activePath,
                'children' => $this->getNav($navPages, $page),
            ];
        }

        return $nav;
    }
}
