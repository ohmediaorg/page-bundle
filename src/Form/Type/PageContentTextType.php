<?php

namespace OHMedia\PageBundle\Form\Type;

use OHMedia\PageBundle\Entity\PageContentText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageContentTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $content = isset($options['data']) ? $options['data'] : null;

        $builder
            ->add('text', TextType::class, [
                'label' => $options['text_label'],
                'required' => $options['required'],
                'data' => $content ? $content->getText() : null,
                'attr' => $options['text_attr'],
            ])
            ->add('name', HiddenType::class, [
                'data' => $builder->getName(),
            ])
            ->add('type', HiddenType::class, [
                'data' => PageContentText::TYPE_TEXT,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageContentText::class,
            'text_attr' => [],
            'text_label' => null,
        ]);
    }
}
