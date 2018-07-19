<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Page;

use EzSystems\EzPlatformPageFieldType\Exception\ZoneNotFoundException;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\BlockValue;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Page;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Zone;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockAttributeDefinition;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinitionFactory;
use EzSystems\EzPlatformPageFieldType\Registry\LayoutDefinitionRegistry;
use EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\XmlProcessor;
use function in_array;

class PageFactory
{
    /** @var \EzSystems\EzPlatformPageFieldType\Registry\LayoutDefinitionRegistry */
    private $layoutDefinitionRegistry;

    /** @var \EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinitionFactory */
    private $blockDefinitionFactory;

    /** @var \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\XmlProcessor */
    private $xmlProcessor;

    /** @var bool */
    private $ignoreUnknownBlocks;

    /**
     * @param \EzSystems\EzPlatformPageFieldType\Registry\LayoutDefinitionRegistry $layoutDefinitionRegistry
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockDefinitionFactory $blockDefinitionFactory
     * @param \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\XmlProcessor $xmlProcessor
     * @param bool $ignoreUnknownBlocks
     */
    public function __construct(
        LayoutDefinitionRegistry $layoutDefinitionRegistry,
        BlockDefinitionFactory $blockDefinitionFactory,
        XmlProcessor $xmlProcessor,
        bool $ignoreUnknownBlocks = false
    ) {
        $this->layoutDefinitionRegistry = $layoutDefinitionRegistry;
        $this->blockDefinitionFactory = $blockDefinitionFactory;
        $this->xmlProcessor = $xmlProcessor;
    }

    /**
     * @param string $xml
     *
     * @return \EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Page
     *
     * @throws \Exception
     */
    public function fromXml(string $xml): Page
    {
        $document = new \DOMDocument();
        $zones = [];
        $blocks = [];

        $document->loadXML($xml);
        $document->normalizeDocument();

        $pageElement = $document->documentElement;
        $pageLayout = $pageElement->getAttribute('layout');

        $layoutDefinition = $this->layoutDefinitionRegistry->getLayoutDefinitionById($pageLayout);
        $layoutDefinitionZoneIds = array_keys($layoutDefinition->getZones());

        $zonesElement = $pageElement->getElementsByTagName('zones')->item(0);

        foreach ($zonesElement->getElementsByTagName('zone') as $index => $zoneElement) {
            $zoneId = $zoneElement->getAttribute('id');

            if (!in_array($zoneId, $layoutDefinitionZoneIds, true) && !isset($layoutDefinitionZoneIds[$index])) {
                throw new ZoneNotFoundException($layoutDefinition);
            }

            $zoneName = $zoneElement->getAttribute('name');

            $blocksElement = $zoneElement->getElementsByTagName('blocks')->item(0);

            foreach ($blocksElement->getElementsByTagName('block') as $blockElement) {
                $blockId = $blockElement->getAttribute('id');
                $blockType = $blockElement->getAttribute('type');
                $blockView = $blockElement->getAttribute('view');

                if (
                    $this->shouldIgnoreUnknownBlocks()
                    && !$this->blockDefinitionFactory->hasBlockDefinition($blockType)
                ) {
                    continue;
                }

                $blockDefinition = $this->blockDefinitionFactory->getBlockDefinition($blockType);

                $attributes = $this->xmlProcessor->processAttributes(
                    $blockDefinition,
                    $blockElement->getElementsByTagName('attributes')->item(0)
                );

                $name = $blockElement->hasAttribute('name')
                    ? $blockElement->getAttribute('name')
                    : '';

                $block = new BlockValue(
                    $blockId,
                    $blockType,
                    $name,
                    $blockView,
                    '',
                    '',
                    '',
                    null,
                    null,
                    $attributes
                );

                $blocks[] = $block;
            }

            $zones[] = new Zone('', $zoneName, $blocks);
            $blocks = [];
        }

        return new Page($pageLayout, $zones);
    }

    /**
     * @return bool
     */
    public function shouldIgnoreUnknownBlocks(): bool
    {
        return $this->ignoreUnknownBlocks;
    }

    /**
     * @param bool $ignoreUnknownBlocks
     */
    public function setIgnoreUnknownBlocks(bool $ignoreUnknownBlocks): void
    {
        $this->ignoreUnknownBlocks = $ignoreUnknownBlocks;
    }

    /**
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\Page\Block\Definition\BlockAttributeDefinition[] $attributes
     *
     * @return string[]
     */
    protected function getAttributeNames(array $attributes): array
    {
        return array_map(
            function (BlockAttributeDefinition $blockAttributeDefinition) {
                return $blockAttributeDefinition->getIdentifier();
            },
            $attributes
        );
    }
}
