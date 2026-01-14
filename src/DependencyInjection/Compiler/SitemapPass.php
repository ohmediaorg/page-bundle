<?php

namespace OHMedia\PageBundle\DependencyInjection\Compiler;

use OHMedia\PageBundle\Controller\SitemapController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SitemapPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // always first check if the primary service is defined
        if (!$container->has(SitemapController::class)) {
            return;
        }

        $definition = $container->findDefinition(SitemapController::class);

        $tagged = $container->findTaggedServiceIds('oh_media_page.sitemap_url_provider');

        foreach ($tagged as $id => $tags) {
            $definition->addMethodCall('addSitemapUrlProvider', [new Reference($id)]);
        }
    }
}
