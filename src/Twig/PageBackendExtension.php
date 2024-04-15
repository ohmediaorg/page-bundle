<?php

namespace OHMedia\PageBundle\Twig;

use OHMedia\PageBundle\Entity\PageContentCheckbox;
use OHMedia\PageBundle\Entity\PageContentImage;
use OHMedia\PageBundle\Entity\PageContentRow;
use OHMedia\PageBundle\Entity\PageContentText;
use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Form\Type\PageContentRowType;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageBackendExtension extends AbstractExtension
{
    private $formFactory;
    private $rendered = false;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
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
        ];
    }

    public function preview(Environment $twig, PageRevision $pageRevision)
    {
        $form = $this->formFactory->create($pageRevision->getTemplate(), $pageRevision);

        $allPageContent = [];

        // TODO: need to be able to check the "label" option
        foreach ($form->all() as $name => $field) {
            $options = $field->getConfig()->getOptions();

            $label = !empty($options['label']) ? $options['label'] : $name;

            $data = $field->getData();

            $type = '';

            if ($data instanceof PageContentCheckbox) {
                $type = 'checkbox';
            } elseif ($data instanceof PageContentImage) {
                $type = 'image';
            } elseif ($data instanceof PageContentRow) {
                $type = 'row';
            } elseif ($data instanceof PageContentText) {
                $type = $data->getType() ?? 'text';
            }

            $allPageContent[] = compact('name', 'label', 'type');
        }

        return $twig->render('@OHMediaPage/page_preview.html.twig', [
            'all_page_content' => $allPageContent,
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
}
