<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forum\Modules\DiscussionWidgetModule;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class DiscussionDiscussionsWidget
 */
class DiscussionDiscussionsWidget extends DiscussionWidgetModule implements ReactWidgetInterface {

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string {
        return "discussion.discussions";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string {
        return "Discussion - Discussions";
    }

    /**
     * @inheridoc
     */
    public function getComponentName(): string {
        return "DiscussionDiscussionsWidget";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetSchema(): Schema {
        $schema = SchemaUtils::composeSchemas(
            parent::getWidgetSchema(),
            self::containerOptionsSchema('containerOptions')
        );
        return $schema;
    }
}
