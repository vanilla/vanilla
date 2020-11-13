<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\MockWidgets;

use Garden\Schema\Schema;
use Vanilla\Contracts\Addons\WidgetInterface;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\AbstractWidgetModule;

/**
 * Class MockWidget1
 */
class MockWidget1 extends AbstractWidgetModule {

    /** @var string */
    private $name;

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([
            'name' => [
                'type' => 'string',
                'x-control' => SchemaForm::textBox(
                    new FormOptions('name', 'name text box description')
                )
            ],
        ]);
    }

    /**
     * @return string
     */
    public function toString(): string {
        return "<div class='mockWidget'>{$this->name}</div>";
    }

    /**
     * @return array|null
     */
    public function getProps(): ?array {
        return [];
    }

    /**
     * @return string
     */
    public function getComponentName(): string {
        return "mock-widget";
    }


    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return "Mock Widget 1";
    }
}
