<?php

namespace Nelmio\ApiDocBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormInfoParserCompilerPass implements CompilerPassInterface
{
    public const TAG_NAME = 'nelmio_api_doc.extractor.form_info_parser';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('nelmio_api_doc.parser.form_type_parser')) {
            return;
        }

        $formParser = $container->findDefinition('nelmio_api_doc.parser.form_type_parser');
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            $formParser->addMethodCall('addFormInfoParser', [new Reference($id)]);
        }
    }
}
