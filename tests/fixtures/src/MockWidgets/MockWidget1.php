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
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\AbstractWidgetModule;

/**
 * Class MockWidget1
 */
class MockWidget1 extends AbstractWidgetModule
{
    /** @var string */
    private $name;

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "name" => [
                "type" => "string",
                "x-control" => SchemaForm::textBox(new FormOptions("Title", "name text box description")),
            ],
            "nested?" => [
                "type" => "object",
                "x-control" => SchemaForm::section(new FormOptions("Nested Params")),
                "properties" => [
                    "slotType:s?" => [
                        "enum" => ["d", "w", "m"],
                        "x-control" => SchemaForm::radio(
                            new FormOptions("Timeframe"),
                            new StaticFormChoices([
                                "d" => "Daily",
                                "w" => "Weekly",
                                "m" => "Monthly",
                            ])
                        ),
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return "<div class='mockWidget'>{$this->name}</div>";
    }

    /**
     * @return array|null
     */
    public function getProps(): ?array
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getComponentName(): string
    {
        return "mock-widget";
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Mock Widget 1";
    }
}
