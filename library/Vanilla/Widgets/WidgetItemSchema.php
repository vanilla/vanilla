<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;

/**
 * Schema for a widget item.
 */
class WidgetItemSchema extends Schema {
    /**
     * Override constructor to initialize schema.
     */
    public function __construct() {
        parent::__construct($this->parseInternal([
            'to:s',
            'iconUrl:s?',
            'imageUrl:s?',
            'name:s',
            'description:s?',
            'counts:a?' => Schema::parse([
                'labelCode:s',
                'count:i',
            ])
        ]));
    }
}
