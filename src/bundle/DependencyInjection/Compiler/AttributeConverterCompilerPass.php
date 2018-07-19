<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformPageMigrationBundle\DependencyInjection\Compiler;

use EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AttributeConverterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ConverterRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(ConverterRegistry::class);
        $converterDefinitions = $container->findTaggedServiceIds('ezplatform.fieldtype.ezlandingpage.migration.attribute.converter');

        foreach ($converterDefinitions as $id => $tags) {
            foreach ($tags as $attributes) {
                $registry->addMethodCall('addConverter', [$attributes['block_type'], new Reference($id)]);
            }
        }
    }
}
