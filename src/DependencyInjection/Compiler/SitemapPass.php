<?php

namespace OHMedia\PageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SitemapPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // always first check if the primary service is defined
        if (!$container->has('oh_media_page.sitemap_controller')) {
            return;
        }

        $definition = $container->findDefinition('oh_media_page.sitemap_controller');

        $tagged = $container->findTaggedServiceIds('oh_media_page.sitemap_url_provider');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addSitemapUrlProvider', [new Reference($id)]);
        }
    }
}
