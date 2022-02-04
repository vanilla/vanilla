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
 * Widget representing a single column section.
 */
class SectionOneColumn extends AbstractLayoutSection {

    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return 'SectionOneColumn';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([
            'contents' => new ReactChildrenSchema('The contents of the schema.'),
            'isNarrow:b' => [
                'default' => false,
            ],
            'breadcrumbs?' => new ReactChildrenSchema(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return '1 Column Layout';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string {
        return 'section.1-column';
    }
}
