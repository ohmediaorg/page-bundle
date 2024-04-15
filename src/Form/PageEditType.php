<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\PageBundle\Entity\Page;
use OHMedia\TimezoneBundle\Form\Type\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $options['data'];

        if ($page->isHomepage()) {
            $lockedHelp = 'The homepage cannot be locked.';

            $publishedHelp = 'The homepage must remain published.';
        } else {
            $lockedHelp = '<i><b>Note:</b> web crawlers will not be able to index a page behind a login form!</i>';

            $publishedHelp = 'This page will not be accessible on the frontend if the published date and time is empty or in the future.';

            if (!$page->isCurrentPageRevisionPublished()) {
                $publishedHelp .= '<br /><i><b>Note:</b> this page will not be considered published until it has published content.</i>';
            }
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
