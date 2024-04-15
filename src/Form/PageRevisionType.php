<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\PageRevision;
use OHMedia\PageBundle\Service\PageManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageRevisionType extends AbstractType
{
    private $pageManager;

    public function __construct(PageManager $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $options['data'];

        $templates = [];

        $pageTemplateTypes = $this->pageManager->getPageTemplateTypes();

        foreach ($pageTemplateTypes as $pageTemplateType) {
            $label = call_user_func([$pageTemplateType, 'getTemplateName']);

            $templates[$label] = $pageTemplateType::class;
        }

        $builder->add('template', ChoiceType::class, [
            'choices' => $templates,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageRevision::class,
        ]);
    }
}
