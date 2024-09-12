<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageQueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageNavigationType extends AbstractType
{
    public function __construct(private PageQueryBuilder $pageQueryBuilder)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $options['data'];

        $builder
            ->add('nav_text', TextType::class, [
                'required' => false,
                'label' => 'Link Text',
                'help' => 'This text will be used in links to this page from the main nav and breadcrumbs.<br><b>Default value:</b> '.$page->getName(),
                'help_html' => true,
            ])
            ->add('dropdown_text', TextType::class, [
                'required' => false,
                'label' => 'Dropdown Text',
                'help' => 'If this page has children, this text will be used in the main nav dropdown.<br><b>Default value:</b> '.$page->getName(),
                'help_html' => true,
            ])
            ->add('new_window', CheckboxType::class, [
                'required' => false,
                'label' => 'Open in a new window in navigation menu',
            ])
            ->add('hidden', CheckboxType::class, [
                'required' => false,
                'label' => 'Exclude from navigation',
            ])
            ->add('redirect_type', ChoiceType::class, [
                'choices' => [
                    'None' => Page::REDIRECT_TYPE_NONE,
                    'Internal' => Page::REDIRECT_TYPE_INTERNAL,
                    'External' => Page::REDIRECT_TYPE_EXTERNAL,
                ],
                'expanded' => true,
            ])
            ->add('redirect_internal', EntityType::class, [
                'label' => 'Internal Page',
                'required' => false,
                'placeholder' => 'None',
                'class' => Page::class,
                'choice_label' => function (Page $page) {
                    return '/'.$page->getPath();
                },
                'query_builder' => $this->pageQueryBuilder
                    ->createQueryBuilder()
                    ->locked(false)
                    ->exclude($page)
                    ->getQueryBuilder()
                    ->orderBy('p.order_global', 'ASC'),
            ])
            ->add('redirect_external', UrlType::class, [
                'required' => false,
                'label' => 'External Page',
                'default_protocol' => null, // makes sure field is type="url"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
