<?php

namespace OHMedia\PageBundle\Form\Type;

use OHMedia\PageBundle\Entity\PageContentCta;
use OHMedia\UtilityBundle\Form\CallToActionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageContentCtaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cta', CallToActionType::class, [
                'label' => $options['cta_label'],
                'required' => $options['required'],
                'attr' => $options['cta_attr'],
                'providers' => $options['cta_providers'],
            ])
            ->add('name', HiddenType::class, [
                'data' => $builder->getName(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageContentCta::class,
            'cta_label' => false,
            'cta_attr' => [],
            'cta_providers' => [],
        ]);
    }
}
