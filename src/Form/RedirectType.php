<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\Redirect;
use OHMedia\UtilityBundle\Service\EntityPathManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectType extends AbstractType
{
    public function __construct(
        private EntityPathManager $entityPathManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $redirect = $options['data'];

        $entityChoices = $this->entityPathManager->getChoices($redirect->getEntity());

        $builder->add('path', TextType::class, [
            'help' => 'Enter a path to redirect to the selected Internal Resource.',
        ]);

        $builder->add('entity', ChoiceType::class, [
            'label' => 'Internal Resource',
            'required' => false,
            'choices' => $entityChoices,
            'placeholder' => '- Select -',
            'help' => 'The redirect will not work if the selected resource becomes unavailable to the public (eg. not published, requires login, deleted, etc.).',
            'label_attr' => [
                'class' => 'required',
            ],
            'attr' => [
                'class' => 'nice-select2',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Redirect::class,
        ]);
    }
}
