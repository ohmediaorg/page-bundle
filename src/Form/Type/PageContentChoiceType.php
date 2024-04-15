<?php

namespace OHMedia\PageBundle\Form\Type;

use OHMedia\PageBundle\Entity\PageContentText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageContentChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $content = isset($options['data']) ? $options['data'] : null;

        $builder
            ->add('text', ChoiceType::class, [
                'label' => false,
                'required' => $options['required'],
                'data' => $content ? $content->getText() : null,
                'attr' => $options['choice_attr'],
                'choices' => $options['choice_choices'],
                'expanded' => $options['choice_expanded'],
            ])
            ->add('name', HiddenType::class, [
                'data' => $builder->getName(),
            ])
            ->add('type', HiddenType::class, [
                'data' => PageContentText::TYPE_CHOICE,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageContentText::class,
            'choice_attr' => [],
            'choice_choices' => [],
            'choice_expanded' => false,
        ]);
    }
}
