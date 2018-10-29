<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformPageMigration\Exception;

use EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Definition\LayoutDefinition;
use RuntimeException;

class ZoneNotFoundException extends RuntimeException
{
    /**
     * @param string $zoneId
     * @param \EzSystems\EzPlatformPageFieldType\FieldType\LandingPage\Definition\LayoutDefinition $layout
     */
    public function __construct(string $zoneId, LayoutDefinition $layout)
    {
        parent::__construct(
            sprintf(
                'Zone with id: "%s" not found for layout: "%s", id: "%s"',
                $zoneId,
                $layout->getName(),
                $layout->getId()
            )
        );
    }
}
