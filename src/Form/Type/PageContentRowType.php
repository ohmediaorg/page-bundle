<?php

namespace OHMedia\PageBundle\Form\Type;

use OHMedia\PageBundle\Entity\PageContentRow;
use OHMedia\WysiwygBundle\Form\Type\WysiwygType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageContentRowType extends AbstractType
{
    public const DATA_ATTRIBUTE = 'data-ohmedia-page-content-row';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $row = isset($options['data']) ? $options['data'] : null;

        $builder
            ->add('layout', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'None',
                'choices' => [
                    '1 Column' => PageContentRow::LAYOUT_ONE_COLUMN,
                    '2 Columns' => PageContentRow::LAYOUT_TWO_COLUMN,
                    '3 Columns' => PageContentRow::LAYOUT_THREE_COLUMN,
                    'Sidebar Left' => PageContentRow::LAYOUT_SIDEBAR_LEFT,
                    'Sidebar Right' => PageContentRow::LAYOUT_SIDEBAR_RIGHT,
                ],
                'data' => $row ? $row->getLayout() : null,
            ])
            ->add('column_1', WysiwygType::class, [
                'data' => $row ? $row->getColumn1() : null,
                'attr' => $options['wysiwyg_attr'],
                'allowed_tags' => $options['allowed_tags'],
                'allow_shortcodes' => $options['allow_shortcodes'],
            ])
            ->add('column_2', WysiwygType::class, [
                'data' => $row ? $row->getColumn2() : null,
                'attr' => $options['wysiwyg_attr'],
                'allowed_tags' => $options['allowed_tags'],
                'allow_shortcodes' => $options['allow_shortcodes'],
            ])
            ->add('column_3', WysiwygType::class, [
                'data' => $row ? $row->getColumn3() : null,
                'attr' => $options['wysiwyg_attr'],
                'allowed_tags' => $options['allowed_tags'],
                'allow_shortcodes' => $options['allow_shortcodes'],
            ])
            ->add('name', HiddenType::class, [
                'data' => $builder->getName(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageContentRow::class,
            'wysiwyg_attr' => [],
            'attr' => [
                self::DATA_ATTRIBUTE => '',
            ],
            'allowed_tags' => null,
            'allow_shortcodes' => true,
        ]);
    }
}
