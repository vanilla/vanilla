<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\MockWidgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Widgets\AbstractWidgetModule;

/**
 * Class MockWidget2
 */
class MockWidget2 extends MockWidget1 {

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return "Mock Widget 2";
    }
}
