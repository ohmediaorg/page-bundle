<?php

namespace OHMedia\PageBundle;

use OHMedia\PageBundle\DependencyInjection\Compiler\PagePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OHMediaPageBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PagePass());
    }
}
