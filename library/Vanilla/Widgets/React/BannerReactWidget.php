<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Widgets\Schema\ReactChildrenSchema;
use Vanilla\Widgets\Schema\ReactSingleChildSchema;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;

/**
 * Widget representing a 3 column layout.
 */
class BannerReactWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface {

    use CombinedPropsWidgetTrait;

    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return 'Banner';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([
            'background' => new WidgetBackgroundSchema(),
            'contents' => new ReactChildrenSchema('The contents of the schema.'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return 'Banner';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string {
        return 'app-banner';
    }
}
