<?php

namespace OHMedia\PageBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use OHMedia\PageBundle\Entity\AbstractPageContent;
use OHMedia\PageBundle\Entity\PageContentText;
use OHMedia\PageBundle\Entity\PageRevision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractPageTemplateType extends AbstractType
{
    abstract public static function getTemplate(): string;

    abstract public static function getTemplateName(): string;

    abstract protected function buildFormContent();

    private ?FormBuilderInterface $builder = null;
    private ?PageRevision $pageRevision = null;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    final public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->pageRevision = $options['data'];

        $this->builder = $builder;

        $this->buildFormContent();
    }

    final public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageRevision::class,
        ]);
    }

    protected function addPageContentCheckbox(string $name, array $options = []): self
    {
        $checkboxLabel = !empty($options['label']) ? $options['label'] : $name;

        $options['checkbox_label'] = $this->generateLabel($checkboxLabel);

        $options['label'] = false;

        $options['row_attr'] = [
            'class' => 'fieldset-nostyle',
        ];

        return $this->addPageContent(
            $name,
            PageContentCheckboxType::class,
            $options,
            $this->pageRevision->getPageContentCheckbox($name)
        );
    }

    protected function addPageContentChoice(string $name, array $options = []): self
    {
        if (isset($options['choices'])) {
            $options['choice_choices'] = $options['choices'];
        }

        unset($options['choices']);

        if (isset($options['expanded'])) {
            $options['choice_expanded'] = $options['expanded'];
        }

        unset($options['expanded']);

        $options['row_attr'] = [
            'class' => 'fieldset-nostyle',
        ];

        return $this->addPageContent(
            $name,
            PageContentChoiceType::class,
            $options,
            $this->pageRevision->getPageContentText($name, PageContentText::TYPE_CHOICE)
        );
    }

    protected function addPageContentImage(string $name, array $options = []): self
    {
        $imageLabel = !empty($options['label']) ? $options['label'] : $name;

        $options['image_label'] = $this->generateLabel($imageLabel);

        $options['label'] = false;

        $options['row_attr'] = [
            'class' => 'fieldset-nostyle',
        ];

        return $this->addPageContent(
            $name,
            PageContentImageType::class,
            $options,
            $this->pageRevision->getPageContentImage($name)
        );
    }

    protected function addPageContentRow(string $name, array $options = []): self
    {
        return $this->addPageContent(
            $name,
            PageContentRowType::class,
            $options,
            $this->pageRevision->getPageContentRow($name)
        );
    }

    protected function addPageContentText(string $name, array $options = []): self
    {
        $options['row_attr'] = [
            'class' => 'fieldset-nostyle',
        ];

        return $this->addPageContent(
            $name,
            PageContentTextType::class,
            $options,
            $this->pageRevision->getPageContentText($name, PageContentText::TYPE_TEXT)
        );
    }

    protected function addPageContentTextarea(string $name, array $options = []): self
    {
        $options['row_attr'] = [
            'class' => 'fieldset-nostyle',
        ];

        return $this->addPageContent(
            $name,
            PageContentTextareaType::class,
            $options,
            $this->pageRevision->getPageContentText($name, PageContentText::TYPE_TEXTAREA)
        );
    }

    protected function addPageContentWysiwyg(string $name, array $options = []): self
    {
        $options['row_attr'] = [
            'class' => 'fieldset-nostyle',
        ];

        return $this->addPageContent(
            $name,
            PageContentWysiwygType::class,
            $options,
            $this->pageRevision->getPageContentText($name, PageContentText::TYPE_WYSIWYG)
        );
    }

    private function addPageContent(
        string $name,
        string $type,
        array $options,
        ?AbstractPageContent $data
    ): self {
        $options['mapped'] = false;

        $options['data'] = $data;

        $this->builder->add($name, $type, $options);

        return $this;
    }

    private function generateLabel(string $name): string
    {
        // implementation from Symfony\Component\Form\FormRenderer::humanize
        return ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $name))));
    }
}
