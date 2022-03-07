<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\Schema\ReactChildrenSchema;

/**
 * Widget representing a layout container.
 *
 * TITLE
 * subtitle
 * description ipsum lorem delorum.
 * ------------------
 * CONTENT
 * ------------------
 *       View All
 */
class WidgetContainerReactWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface {

    use HomeWidgetContainerSchemaTrait;
    use CombinedPropsWidgetTrait;

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string {
        return 'WidgetContainer';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema('subtitle'),
            self::widgetDescriptionSchema(),
            self::containerOptionsSchema(),
            Schema::parse([
                'children' => new ReactChildrenSchema(),
            ])
        );
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return 'Widget Container';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string {
        return 'widget-container';
    }
}
