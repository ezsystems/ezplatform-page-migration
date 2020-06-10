<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Exception;
use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use eZ\Publish\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use eZ\Publish\Core\Persistence\Legacy\Content\Handler as ContentHandler;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\Type\Handler as ContentTypeHandler;
use EzSystems\EzPlatformPageMigration\Converter\FieldValueConverter;
use EzSystems\EzPlatformPageFieldType\Exception\BlockDefinitionNotFoundException;
use EzSystems\EzPlatformPageFieldType\Exception\PageNotFoundException;
use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Page;
use EzSystems\EzPlatformPageFieldType\FieldType\Page\Storage\Gateway;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function count;

class MigrateDataCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'ezplatform:page:migrate';

    /** @var \eZ\Publish\Core\Persistence\Legacy\Content\Handler */
    protected $contentHandler;

    /** @var \eZ\Publish\SPI\Persistence\Content\Type\Handler */
    protected $contentTypeHandler;

    /** @var \EzSystems\EzPlatformPageMigration\Converter\FieldValueConverter */
    private $legacyConverter;

    /** @var \eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry */
    private $converterRegistry;

    /** @var \EzSystems\EzPlatformPageFieldType\FieldType\Page\Storage\Gateway */
    private $pageGateway;

    /** @var \eZ\Publish\Core\Persistence\Legacy\Content\Gateway */
    private $contentGateway;

    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /**
     * @param null|string $name
     * @param \EzSystems\EzPlatformPageMigration\Converter\FieldValueConverter $legacyConverter
     * @param \eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry $converterRegistry
     * @param \eZ\Publish\Core\Persistence\Legacy\Content\Handler $contentHandler
     * @param \eZ\Publish\SPI\Persistence\Content\Type\Handler $contentTypeHandler
     * @param \eZ\Publish\Core\Persistence\Legacy\Content\Gateway $contentGateway
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\Page\Storage\Gateway $pageGateway
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(
        ?string $name = null,
        FieldValueConverter $legacyConverter,
        ConverterRegistry $converterRegistry,
        ContentHandler $contentHandler,
        ContentTypeHandler $contentTypeHandler,
        ContentGateway $contentGateway,
        Gateway $pageGateway,
        Connection $connection
    ) {
        parent::__construct($name);

        $this->legacyConverter = $legacyConverter;
        $this->converterRegistry = $converterRegistry;
        $this->pageGateway = $pageGateway;
        $this->contentHandler = $contentHandler;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->contentGateway = $contentGateway;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('Migrate Landing Pages from eZ Platform <= 2.1')
            ->setHelp(<<<'EOF'
            
<info>%command.name%</info> runs a migration process for Landing Pages created in previous versions of eZ Platform. 

<warning>During the script execution the database should not be modified.

To avoid surprises you are advised to create a backup or execute a dry run:
 
    %command.name% --dry-run
    
before proceeding with the actual update.</warning>

Since this script can potentially run for a very long time, to avoid memory
exhaustion, run it in production environment using the <info>--env=prod</info> switch.

If you configuration uses multiple repositories, 
you have to run the comand multiple times 
with different siteaccesses using <info>--siteaccess</info> switch.

EOF
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'When specified, changes are _NOT_ persisted to database.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = $input->getOption('dry-run');
        $io = new SymfonyStyle($input, $output);

        if ($isDryRun) {
            $io->note('<info>--dry-run</info> switch activated. Operation won\'t be persisted to the database.');
        }

        $io->warning('You are about to run data migration process for Landing Pages. This operation cannot be reverted.');

        // inject custom field value converter to transform XML into Page object
        $converter = $this->converterRegistry->getConverter('ezlandingpage');
        $this->converterRegistry->register('ezlandingpage', $this->legacyConverter);

        $contentIds = $this->findLandingPages();

        if (empty($contentIds)) {
            $io->writeln('Found 0 content items. Exiting...');

            return;
        }

        $question = sprintf('Found %d content items. Do you want to continue?', count($contentIds));
        if (!$io->confirm($question, false)) {
            return;
        }

        /** @var \eZ\Publish\SPI\Persistence\Content\VersionInfo[] $spiVersions */
        $spiVersions = array_merge(...array_map([$this->contentHandler, 'listVersions'], $contentIds));

        $io->newLine();

        if (!$isDryRun) {
            $this->connection->beginTransaction();
        }

        foreach ($spiVersions as $spiVersionInfo) {
            try {
                $content = $this->contentHandler->load(
                    $spiVersionInfo->contentInfo->id,
                    $spiVersionInfo->versionNo,
                    $spiVersionInfo->languageCodes
                );
            } catch (BlockDefinitionNotFoundException $e) {
                $io->error(sprintf('Cannot find block definition for block type "%s".', $e->getBlockType()));
                $io->newLine();

                if (!$io->confirm('Do you want to ignore unknown blocks in migrated Landing Pages?', true)) {
                    if (!$isDryRun) {
                        $this->connection->rollBack();
                    }

                    return;
                }

                $this->legacyConverter->setIgnoreUnknownBlocks(true);
                $content = $this->contentHandler->load(
                    $spiVersionInfo->contentInfo->id,
                    $spiVersionInfo->versionNo,
                    $spiVersionInfo->languageCodes
                );
            }

            $contentId = (int) $spiVersionInfo->contentInfo->id;
            $versionNo = (int) $spiVersionInfo->versionNo;

            foreach ($content->fields as $field) {
                if (!$this->isPageField($field)) {
                    continue;
                }

                $languageCode = $field->languageCode;

                // Versions with STATUS_INTERNAL_DRAFT may not have name...
                if ( $spiVersionInfo->names != null ) {
                    $contentName = current($spiVersionInfo->names);
                } else {
                    $contentName = null;
                }

                $pageIdentifierString = sprintf(
                    '"%s" [contentId: %s, versionNo: %s, languageCode: %s]',
                    $contentName,
                    $contentId,
                    $versionNo,
                    $languageCode
                );

                $io->newLine();
                $io->section($pageIdentifierString);

                if ($this->doesPageExist($contentId, $versionNo, $languageCode)) {
                    $io->note('Page has already been migrated. Skipping...');
                    continue;
                }

                $page = $field->value->externalData;

                try {
                    if (!$isDryRun) {
                        $this->insertPage($contentId, $versionNo, $languageCode, $page);
                    }
                } catch (Exception $e) {
                    $io->error(sprintf('Cannot save Page due to the error: %s', $e->getMessage()));
                    continue;
                }

                $io->success('Page has been successfully migrated');
            }
        }

        if (!$isDryRun) {
            $this->connection->commit();
        }

        // bring back old converter
        $this->converterRegistry->register('ezlandingpage', $converter);
    }

    /**
     * @return int[]
     */
    protected function findLandingPages(): array
    {
        $ezLandingPageContentTypes = [];
        $contentTypeGroups = $this->contentTypeHandler->loadAllGroups();
        foreach ($contentTypeGroups as $group) {
            $contentTypes = $this->contentTypeHandler->loadContentTypes($group->id);
            foreach ($contentTypes as $contentType) {
                foreach ($contentType->fieldDefinitions as $fieldDefinition) {
                    if ($fieldDefinition->fieldType === 'ezlandingpage') {
                        $ezLandingPageContentTypes[] = $contentType;
                    }
                }
            }
        }

        $contentIds = [];
        foreach ($ezLandingPageContentTypes as $contentType) {
            $contentIds = array_merge($contentIds, $this->contentGateway->getContentIdsByContentTypeId($contentType->id));
        }

        return $contentIds;
    }

    /**
     * @param \eZ\Publish\SPI\Persistence\Content\Field $field
     *
     * @return bool
     */
    private function isPageField(Field $field): bool
    {
        return 'ezlandingpage' === $field->type;
    }

    /**
     * @param int $contentId
     * @param int $versionNo
     * @param string $languageCode
     *
     * @return bool
     */
    private function doesPageExist(int $contentId, int $versionNo, string $languageCode): bool
    {
        try {
            $this->pageGateway->loadPageByContentId($contentId, $versionNo, $languageCode);
        } catch (PageNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param int $contentId
     * @param int $versionNo
     * @param string $languageCode
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Model\Page $page
     */
    private function insertPage(
        int $contentId,
        int $versionNo,
        string $languageCode,
        Page $page
    ): void {
        $pageId = $this->pageGateway->insertPage(
            $contentId,
            $versionNo,
            $languageCode,
            $page->getLayout()
        );

        foreach ($page->getZones() as $zone) {
            $zoneId = $this->pageGateway->insertZone($zone->getName());

            foreach ($zone->getBlocks() as $blockValue) {
                $blockId = $this->pageGateway->insertBlock(
                    $blockValue->getType(),
                    $blockValue->getName(),
                    $blockValue->getView()
                );
                $this->pageGateway->insertBlockDesign(
                    $blockId,
                    $blockValue->getStyle(),
                    $blockValue->getCompiled(),
                    $blockValue->getClass()
                );
                $this->pageGateway->insertBlockVisibility(
                    $blockId,
                    null !== $blockValue->getSince()
                        ? $blockValue->getSince()->getTimestamp()
                        : null,
                    null !== $blockValue->getTill()
                        ? $blockValue->getTill()->getTimestamp()
                        : null
                );

                foreach ($blockValue->getAttributes() as $attribute) {
                    $attributeId = $this->pageGateway->insertAttribute(
                        $attribute->getName(),
                        $attribute->getValue()
                    );
                    $this->pageGateway->assignAttributeToBlock($attributeId, $blockId);
                }

                $this->pageGateway->assignBlockToZone($blockId, $zoneId);
            }

            $this->pageGateway->assignZoneToPage($zoneId, $pageId);
        }
    }
}
