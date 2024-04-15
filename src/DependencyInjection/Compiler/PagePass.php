<?php

namespace OHMedia\PageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PagePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // always first check if the primary service is defined
        if (!$container->has('oh_media_page.page_manager')) {
            return;
        }

        $definition = $container->findDefinition('oh_media_page.page_manager');

        $tagged = $container->findTaggedServiceIds('oh_media_page.page_template_type');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addPageTemplateType', [new Reference($id)]);
        }
    }
}
