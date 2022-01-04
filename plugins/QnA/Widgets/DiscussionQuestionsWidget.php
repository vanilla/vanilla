<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forum\Modules\QnAWidgetModule;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Class DiscussionQuestionsWidget
 */
class DiscussionQuestionsWidget extends QnAWidgetModule implements ReactWidgetInterface {

    /**
     * @inheridoc
     */
    public static function getWidgetID(): string {
        return "discussion.questions";
    }

    /**
     * @inheridoc
     */
    public static function getWidgetName(): string {
        return "Discussion - Questions";
    }

    /**
     * @inheridoc
     */
    public function getComponentName(): string {
        return "DiscussionQuestionsWidget";
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
