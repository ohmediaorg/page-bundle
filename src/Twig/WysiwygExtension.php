<?php

namespace OHMedia\PageBundle\Twig;

use OHMedia\PageBundle\Repository\PageRepository;
use OHMedia\WysiwygBundle\Twig\AbstractWysiwygExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;

class WysiwygExtension extends AbstractWysiwygExtension
{
    public function __construct(
        private PageRepository $pageRepository,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('page_link', [$this, 'pageLink']),
        ];
    }

    public function pageLink(int $id = null)
    {
        $page = $this->pageRepository->find($id);

        return $this->urlGenerator->generate('oh_media_page_frontend', [
            'path' => $page ? $page->getPath() : '',
        ]);
    }
}
