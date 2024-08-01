<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Utility\SchemaUtils;

/**
 * Class DiscussionWidgetModule
 * @deprecated Use DiscussionAnnouncementsWidget instead.
 * @package Vanilla\Forum\Modules
 */
class AnnouncementWidgetModule extends BaseDiscussionWidgetModule
{
    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "List - Announcements";
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
                self::categorySchema(),
                self::siteSectionIDSchema(),
                self::sortSchema(),
                self::limitSchema()
            )
        );
        return $apiSchema;
    }

    /**
     * Get the real parameters that we will pass to the API.
     * @param array|null $params
     * @return array
     * @throws ValidationException
     */
    protected function getRealApiParams(?array $params = null): array
    {
        $apiParams = parent::getRealApiParams();
        $apiParams["pinned"] = true;

        return $apiParams;
    }
}
