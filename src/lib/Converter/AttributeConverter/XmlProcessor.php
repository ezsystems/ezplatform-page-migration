<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Converter\AttributeConverter;

use DOMNode;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinition;

class XmlProcessor
{
    /** @var \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterRegistry */
    private $registry;

    /**
     * @param \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterRegistry $registry
     */
    public function __construct(ConverterRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinition $blockDefinition
     * @param \DOMNode $node
     *
     * @return \EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Attribute[]
     */
    public function processAttributes(BlockDefinition $blockDefinition, DOMNode $node): array
    {
        $converter = $this->registry->getConverter($blockDefinition->getIdentifier());

        if (null === $converter) {
            return [];
        }

        return $converter->convert($blockDefinition, $node);
    }
}
