<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Converter\AttributeConverter;

use DOMNode;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Attribute;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinition;

class CollectionConverter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert(
        BlockDefinition $blockDefinition,
        DOMNode $node
    ): array {
        $locationIds = [];
        /** @var \DOMElement $childElement */
        foreach ($node->childNodes as $childElement) {
            if ('locationlist' !== $childElement->nodeName) {
                continue;
            }

            foreach ($childElement->getElementsByTagName('locationId') as $locationIdElement) {
                $locationIds[] = $locationIdElement->nodeValue;
            }
        }

        $attributes[] = new Attribute('', 'locationlist', implode(',', $locationIds));

        return $attributes;
    }
}
