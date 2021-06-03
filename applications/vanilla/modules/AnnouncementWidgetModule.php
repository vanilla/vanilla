<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\Schema\Schema;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Utility\SchemaUtils;

/**
 * Class DiscussionWidgetModule
 *
 * @package Vanilla\Forum\Modules
 */
class AnnouncementWidgetModule extends BaseDiscussionWidgetModule {

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "List - Announcements";
    }

    /**
     * @inheritDoc
     */
    public static function getApiSchema(): Schema {
        $apiSchema = parent::getApiSchema();
        $apiSchema->merge(
            SchemaUtils::composeSchemas(
                self::categorySchema(),
                self::siteSectionIDSchema(),
                self::limitSchema()
            )
        );

        return $apiSchema;
    }

    /**
     * @inheritDoc
     */
    protected function getRealApiParams(): array {
        $apiParams = parent::getRealApiParams();
        $apiParams['pinned'] = true;
        $apiParams['sort'] = '-dateInserted';

        return $apiParams;
    }
}
