<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\Schema\Schema;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Utility\SchemaUtils;

/**
 * Class DiscussionWidgetModule
 *
 * @deprecated Use DiscussionDiscussionsWidget instead.
 * @package Vanilla\Forum\Modules
 */
class DiscussionWidgetModule extends BaseDiscussionWidgetModule
{
    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "List - Discussions";
    }

    /**
     * @inheritDoc
     */
    public static function getApiSchema(): Schema
    {
        $apiSchema = parent::getApiSchema();

        $apiSchema = $apiSchema->merge(
            SchemaUtils::composeSchemas(
                self::followedCategorySchema(),
                static::categorySchema(),
                self::siteSectionIDSchema(),
                self::sortSchema(),
                self::getSlotTypeSchema(),
                self::limitSchema()
            )
        );

        return $apiSchema;
    }
}
