<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Section;

use Garden\Schema\Schema;
use Vanilla\Widgets\Schema\ReactChildrenSchema;

/**
 * Widget representing a section that extends to the edge of the page.
 */
class SectionFullWidth extends AbstractLayoutSection {

    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return 'SectionFullWidth';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([
            'children' => new ReactChildrenSchema(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return 'Full Width Layout';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string {
        return 'section.full-width';
    }
}
