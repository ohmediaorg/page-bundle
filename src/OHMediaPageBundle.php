<?php

namespace OHMedia\PageBundle;

use OHMedia\PageBundle\DependencyInjection\Compiler\PagePass;
use OHMedia\PageBundle\Form\Type\AbstractPageTemplateType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class OHMediaPageBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PagePass());
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $containerConfigurator,
        ContainerBuilder $containerBuilder
    ): void {
        $containerConfigurator->import('../config/services.yaml');

        $containerBuilder->registerForAutoconfiguration(AbstractPageTemplateType::class)
            ->addTag('oh_media_page.page_template_type')
        ;
    }
}
