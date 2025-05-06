<?php

namespace OHMedia\PageBundle\Twig;

use OHMedia\PageBundle\Entity\PageContentRow;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Form\Type\PageContentRowType;
use OHMedia\PageBundle\Service\PageUserTypes;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageBackendExtension extends AbstractExtension
{
    private bool $rendered = false;

    public function __construct(private PageUserTypes $pageUserTypes)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('page_preview', [$this, 'preview'], [
                'is_safe' => ['html'],
                'needs_environment' => 'true',
            ]),
            new TwigFunction('page_script', [$this, 'script'], [
                'is_safe' => ['html'],
                'needs_environment' => 'true',
            ]),
            new TwigFunction('has_page_user_types', [$this, 'hasUserTypes']),
            new TwigFunction('page_user_type', [$this, 'userType']),
        ];
    }

    public function preview(Environment $twig, PageRevision $pageRevision)
    {
        return $twig->render('@OHMediaPage/page_preview.html.twig', [
            'page_revision' => $pageRevision,
        ]);
    }

    public function script(Environment $twig)
    {
        if ($this->rendered) {
            return '';
        }

        $this->rendered = true;

        return $twig->render('@OHMediaPage/page_script.html.twig', [
            'LAYOUT_ONE_COLUMN' => PageContentRow::LAYOUT_ONE_COLUMN,
            'LAYOUT_THREE_COLUMN' => PageContentRow::LAYOUT_THREE_COLUMN,
            'DATA_ATTRIBUTE' => PageContentRowType::DATA_ATTRIBUTE,
        ]);
    }

    public function hasUserTypes(): bool
    {
        return count($this->pageUserTypes->getUserTypes()) > 0;
    }

    public function userType(string $type): string
    {
        return PageUserTypes::readable($type);
    }
}
