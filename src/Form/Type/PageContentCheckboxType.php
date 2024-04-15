<?php

namespace OHMedia\PageBundle\Form\Type;

use OHMedia\PageBundle\Entity\PageContentCheckbox;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageContentCheckboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $checkbox = isset($options['data']) ? $options['data'] : null;

        $builder
            ->add('checked', CheckboxType::class, [
                'label' => $options['checkbox_label'],
                'required' => false,
                'data' => $checkbox ? $checkbox->getChecked() : false,
                'attr' => $options['checkbox_attr'],
            ])
            ->add('name', HiddenType::class, [
                'data' => $builder->getName(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageContentCheckbox::class,
            'checkbox_label' => false,
            'checkbox_attr' => [],
        ]);
    }
}
