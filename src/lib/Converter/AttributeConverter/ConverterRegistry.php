<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Converter\AttributeConverter;

class ConverterRegistry
{
    /** @var \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterInterface[] */
    private $converters;

    /**
     * @param \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterInterface[]|iterable $converters
     */
    public function __construct(array $converters = [])
    {
        $this->converters = $converters;
    }

    /**
     * @param \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterInterface[] $converters
     */
    public function setConverters(array $converters): void
    {
        $this->converters = $converters;
    }

    /**
     * @return \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterInterface[]
     */
    public function getConverters(): array
    {
        return $this->converters;
    }

    /**
     * @param string $blockTypeIdentifier
     * @param \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterInterface $converter
     */
    public function addConverter(string $blockTypeIdentifier, ConverterInterface $converter): void
    {
        $this->converters[$blockTypeIdentifier] = $converter;
    }

    /**
     * @param string $blockTypeIdentifier
     *
     * @return \EzSystems\EzPlatformPageMigration\Converter\AttributeConverter\ConverterInterface|null
     */
    public function getConverter(string $blockTypeIdentifier): ?ConverterInterface
    {
        if (isset($this->converters[$blockTypeIdentifier])) {
            return $this->converters[$blockTypeIdentifier];
        }

        return null;
    }
}
