<?php

namespace Nelmio\ApiDocBundle;

use Nelmio\ApiDocBundle\DependencyInjection\ExtractorHandlerCompilerPass;
use Nelmio\ApiDocBundle\DependencyInjection\FormInfoParserCompilerPass;
use Nelmio\ApiDocBundle\DependencyInjection\LoadExtractorParsersPass;
use Nelmio\ApiDocBundle\DependencyInjection\RegisterExtractorParsersPass;
use Nelmio\ApiDocBundle\DependencyInjection\SwaggerConfigCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NelmioApiDocBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new LoadExtractorParsersPass());
        $container->addCompilerPass(new RegisterExtractorParsersPass());
        $container->addCompilerPass(new ExtractorHandlerCompilerPass());
        $container->addCompilerPass(new SwaggerConfigCompilerPass());
        $container->addCompilerPass(new FormInfoParserCompilerPass());
    }
}
