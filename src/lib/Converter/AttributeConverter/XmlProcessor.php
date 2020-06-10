<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Converter\AttributeConverter;

use DOMNode;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinition;
use Psr\Log\LoggerInterface as Logger;

class XmlProcessor
{
    /** @var \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterRegistry */
    private $registry;

    /** @var Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterRegistry $registry
     */
    public function __construct(ConverterRegistry $registry, Logger $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
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
            $this->logger->warning("No converter for BlockDefinition : {$blockDefinition->getIdentifier()}");
            return [];
        }

        return $converter->convert($blockDefinition, $node);
    }
}
