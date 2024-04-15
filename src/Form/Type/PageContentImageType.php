<?php

namespace OHMedia\PageBundle\Form\Type;

use OHMedia\FileBundle\Form\Type\FileEntityType;
use OHMedia\PageBundle\Entity\PageContentImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageContentImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $image = isset($options['data']) ? $options['data'] : null;

        $builder
            ->add('image', FileEntityType::class, [
                'label' => $options['image_label'],
                'required' => $options['required'],
                'data' => $image ? $image->getImage() : null,
                'attr' => $options['image_attr'],
                'image' => true,
            ])
            ->add('name', HiddenType::class, [
                'data' => $builder->getName(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageContentImage::class,
            'image_label' => false,
            'image_attr' => [],
        ]);
    }
}
