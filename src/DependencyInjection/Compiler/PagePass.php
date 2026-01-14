<?php

namespace OHMedia\PageBundle\DependencyInjection\Compiler;

use OHMedia\PageBundle\Service\PageManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PagePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // always first check if the primary service is defined
        if (!$container->has(PageManager::class)) {
            return;
        }

        $definition = $container->findDefinition(PageManager::class);

        $tagged = $container->findTaggedServiceIds('oh_media_page.page_template_type');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addPageTemplateType', [new Reference($id)]);
        }
    }
}
