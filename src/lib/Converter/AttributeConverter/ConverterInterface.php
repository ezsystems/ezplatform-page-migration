<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Converter\AttributeConverter;

use DOMNode;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinition;

interface ConverterInterface
{
    /**
     * Converts $node to Attribute objects and attaches them to the $objectStorage.
     *
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinition $blockDefinition
     * @param \DOMNode $node
     *
     * @return \EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Attribute[]
     */
    public function convert(BlockDefinition $blockDefinition, DOMNode $node): array;
}
