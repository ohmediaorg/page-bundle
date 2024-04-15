<?php

namespace OHMedia\PageBundle\Form\Type;

use OHMedia\PageBundle\Entity\PageContentText;
use OHMedia\WysiwygBundle\Form\Type\WysiwygType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageContentWysiwygType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $content = isset($options['data']) ? $options['data'] : null;

        if (!isset($options['wysiwyg_attr']['class'])) {
            $options['wysiwyg_attr']['class'] = 'wysiwyg';
        } else {
            $classes = explode(' ', $options['wysiwyg_attr']['class']);

            if (!in_array('wysiwyg', $classes)) {
                $classes[] = 'wysiwyg';
            }

            $options['wysiwyg_attr']['class'] = implode(' ', $classes);
        }

        $builder
            ->add('text', WysiwygType::class, [
                'label' => false,
                'required' => false,
                'data' => $content ? $content->getText() : null,
                'attr' => $options['wysiwyg_attr'],
            ])
            ->add('name', HiddenType::class, [
                'data' => $builder->getName(),
            ])
            ->add('type', HiddenType::class, [
                'data' => PageContentText::TYPE_WYSIWYG,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageContentText::class,
            'wysiwyg_attr' => [],
        ]);
    }
}
