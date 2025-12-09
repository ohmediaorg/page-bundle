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

        $builder
            ->add('text', WysiwygType::class, [
                'label' => $options['wysiwyg_label'],
                'data' => $content ? $content->getText() : null,
                'attr' => $options['wysiwyg_attr'],
                'allowed_tags' => $options['allowed_tags'],
                'allow_shortcodes' => $options['allow_shortcodes'],
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
            'wysiwyg_label' => null,
            'allowed_tags' => null,
            'allow_shortcodes' => true,
        ]);
    }
}
