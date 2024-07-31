<?php

namespace OHMedia\PageBundle\Form;

use OHMedia\MetaBundle\Form\Type\MetaEntityType;
use OHMedia\PageBundle\Entity\Page;
use OHMedia\PageBundle\Service\PageQueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageSEOType extends AbstractType
{
    public function __construct(private PageQueryBuilder $pageQueryBuilder)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $options['data'];

        if ($page->isHomepage()) {
            $noindexHelp = 'The homepage must be indexable.';

            $sitemapHelp = 'The homepage must be in the sitemap.';

            $canonicalHelp = 'The homepage must be canonical to itself.';
        } else {
            $noindexHelp = 'A locked page will not be indexable.';

            $sitemapHelp = 'A page must be published and not locked to appear in the sitemap.';

            $canonicalHelp = 'Search bots will not index a page that specifies a different <a href="https://www.google.com/search?q=what+is+a+canonical+url" target="_blank">canonical URL</a>.';
        }

        $builder
            ->add('meta', MetaEntityType::class, [
                'data' => $page ? $page->getMeta() : null,
            ])
            ->add('noindex', CheckboxType::class, [
                'required' => false,
                'label' => 'Tell search bots not to index this page',
                'help' => $noindexHelp,
                'disabled' => $page->isHomepage(),
            ])
            ->add('sitemap', CheckboxType::class, [
                'required' => false,
                'label' => 'Include this page in sitemap.xml',
                'help' => $sitemapHelp,
                'disabled' => $page->isHomepage(),
            ])
            ->add('canonical', EntityType::class, [
                'label' => 'Canonical URL',
                'help' => $canonicalHelp,
                'help_html' => true,
                'required' => false,
                'placeholder' => 'Self',
                'class' => Page::class,
                'choice_label' => function (Page $page) {
                    return '/'.$page->getPath();
                },
                'query_builder' => $this->pageQueryBuilder
                    ->createQueryBuilder()
                    ->locked(false)
                    ->exclude($page)
                    ->getQueryBuilder()
                    ->orderBy('p.order_global', 'ASC'),
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
