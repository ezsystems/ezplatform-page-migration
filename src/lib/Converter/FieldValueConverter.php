<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Converter;

use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldValue;
use eZ\Publish\SPI\Persistence\Content\FieldValue;
use eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Type;
use EzSystems\EzPlatformPageMigration\Page\PageFactory;

/**
 * Converter for field values in legacy storage.
 */
class FieldValueConverter implements Converter
{
    /** @var \EzSystems\EzPlatformPageMigration\Page\PageFactory */
    private $pageFactory;

    /** @var \EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Type */
    private $pageFieldType;

    /** @var bool */
    private $ignoreUnknownBlocks;

    /**
     * @param \EzSystems\EzPlatformPageMigration\Page\PageFactory $pageFactory
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Type $pageFieldType
     * @param bool $ignoreUnknownBlocks
     */
    public function __construct(PageFactory $pageFactory, Type $pageFieldType, bool $ignoreUnknownBlocks = false)
    {
        $this->pageFactory = $pageFactory;
        $this->pageFieldType = $pageFieldType;
        $this->ignoreUnknownBlocks = $ignoreUnknownBlocks;
    }

    /**
     * {@inheritdoc}
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue)
    {
        $this->pageFactory->setIgnoreUnknownBlocks($this->shouldIgnoreUnknownBlocks());

        $fieldValue->externalData = null !== $value->dataText
            ? $this->pageFactory->fromXml($value->dataText)
            : $this->pageFieldType->getEmptyValue()->getPage();
    }

    /**
     * {@inheritdoc}
     */
    public function toStorageFieldDefinition(FieldDefinition $fieldDef, StorageFieldDefinition $storageDef)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexColumn()
    {
        return false;
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
}
