<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageQueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageCreateType extends AbstractType
{
    public function __construct(private PageQueryBuilder $pageQueryBuilder)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('slug', TextType::class, [
                'required' => false,
                'help' => 'Leave this blank to auto-generate based on the Name.',
                'empty_data' => '',
                'attr' => [
                    'aria-label' => 'Page Slug',
                ],
            ])
            ->add('parent', EntityType::class, [
                'label' => 'Parent Page',
                'required' => false,
                'placeholder' => '/',
                'class' => Page::class,
                'choice_label' => function (Page $page) {
                    return '/'.$page->getPath().'/';
                },
                'query_builder' => $this->pageQueryBuilder
                    ->createQueryBuilder()
                    ->homepage(false)
                    ->getQueryBuilder()
                    ->orderBy('p.order_global', 'ASC'),
                'attr' => [
                    'style' => 'direction:rtl',
                    'aria-label' => 'Parent Page',
                ],
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
