<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\UtilityBundle\Service\EntityPathManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageNavigationType extends AbstractType
{
    public function __construct(
        private EntityPathManager $entityPathManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $options['data'];

        $entityChoices = $this->entityPathManager->getChoices($page->getRedirectInternal());

        $builder
            ->add('nav_text', TextType::class, [
                'required' => false,
                'label' => 'Link Text',
                'help' => 'This text will be used in links to this page from the main nav and breadcrumbs.<br><b>Default value:</b> '.$page->getName(),
                'help_html' => true,
            ])
            ->add('dropdown_text', TextType::class, [
                'required' => false,
                'label' => 'Dropdown Text',
                'help' => 'If this page has children, this text will be used in the main nav dropdown.<br><b>Default value:</b> '.$page->getName(),
                'help_html' => true,
            ])
            ->add('dropdown_only', CheckboxType::class, [
                'required' => false,
                'label' => 'Use as dropdown only',
                'help' => 'If this page has children and this option is checked, the navigation will not contain a link to this page and if a user tries to navigate to this page, they will be redirected to the first available child page.',
            ])
            ->add('new_window', CheckboxType::class, [
                'required' => false,
                'label' => 'Open in a new window in navigation menu',
            ])
            ->add('hidden', CheckboxType::class, [
                'required' => false,
                'label' => 'Exclude from navigation',
                'help' => 'Child pages will also be excluded from navigation.',
            ])
            ->add('redirect_type', ChoiceType::class, [
                'choices' => [
                    'None' => Page::REDIRECT_TYPE_NONE,
                    'Internal' => Page::REDIRECT_TYPE_INTERNAL,
                    'External' => Page::REDIRECT_TYPE_EXTERNAL,
                ],
                'expanded' => true,
                'row_attr' => [
                    'class' => 'fieldset-nostyle',
                ],
            ])
            ->add('redirect_internal', ChoiceType::class, [
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
                'choice_attr' => function ($choice, string $key, mixed $value) use ($page) {
                    if ($value === Page::class.':'.$page->getId()) {
                        return [
                            'disabled' => true,
                        ];
                    }

                    return [];
                },
            ])
            ->add('redirect_external', UrlType::class, [
                'required' => false,
                'label' => 'External URL',
                'default_protocol' => null, // makes sure field is type="url"
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
