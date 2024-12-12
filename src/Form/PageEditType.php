<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Util\ReadableUserType;
use OHMedia\SecurityBundle\Entity\User;
use OHMedia\SecurityBundle\Repository\UserRepository;
use OHMedia\TimezoneBundle\Form\Type\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageEditType extends AbstractType
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $options['data'];

        if ($page->isHomepage()) {
            $lockedHelp = 'The homepage cannot be locked.';

            $publishedHelp = 'The homepage must remain published.';

            $userTypes = [];
        } else {
            $lockedHelp = '<i><b>Note:</b> web crawlers will not be able to index a page behind a login form!</i>';

            $publishedHelp = 'This page will not be accessible on the frontend if the published date and time is empty or in the future.';

            if (!$page->isCurrentPageRevisionPublished()) {
                $publishedHelp .= '<br /><i><b>Note:</b> this page will not be considered published until it has published content.</i>';
            }

            $userTypes = $this->userRepository->createQueryBuilder('u')
                ->select('u.type')
                ->where('u.type NOT IN (:admin_types)')
                ->setParameter('admin_types', [
                    User::TYPE_DEVELOPER,
                    User::TYPE_SUPER,
                    User::TYPE_ADMIN,
                ])
                ->orderBy('u.type', 'ASC')
                ->groupBy('u.type')
                ->getQuery()
                ->getResult();
        }

        $builder
            ->add('name')
            ->add('slug', TextType::class, [
                'required' => false,
                'help' => 'Leave this blank to auto-generate based on the Name.',
                'empty_data' => '',
            ])
            ->add('locked', CheckboxType::class, [
                'required' => false,
                'label' => 'Require login to view page',
                'help' => $lockedHelp,
                'help_html' => true,
                'disabled' => $page->isHomepage(),
            ])
        ;

        $this->addLockedUserTypes($builder, $userTypes, $page);

        $builder
            ->add('published', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'help' => $publishedHelp,
                'help_html' => true,
                'disabled' => $page->isHomepage(),
            ])
        ;
    }

    private function addLockedUserTypes(
        FormBuilderInterface $builder,
        array $userTypes,
        Page $page,
    ): void {
        if (!$userTypes) {
            return;
        }

        $choices = [
            'All Users' => null,
        ];

        foreach ($userTypes as $userType) {
            $type = $userType['type'];

            if ($text = ReadableUserType::get($type)) {
                $choices[$text] = $type;
            }
        }

        $builder->add('locked_user_types', ChoiceType::class, [
            'label' => 'Accessible by these types of logged-in users:',
            'required' => false,
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true,
            'row_attr' => [
                'class' => 'fieldset-nostyle mb-3',
                'style' => $page->isLocked() ? '' : 'display:none',
            ],
            'help' => 'Super Admins and Admins will always be able to view a page that requires login.',
        ]);

        $builder->get('locked_user_types')
            ->addModelTransformer(new CallbackTransformer(
                function (?array $entityValue): array {
                    return is_null($entityValue) ? [null] : $entityValue;
                },
                function (array $formValue): ?array {
                    return !$formValue || in_array(null, $formValue) ? null : $formValue;
                },
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
