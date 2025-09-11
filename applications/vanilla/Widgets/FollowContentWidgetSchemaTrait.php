<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;

use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

trait FollowContentWidgetSchemaTrait
{
    use HomeWidgetContainerSchemaTrait;

    /**
     * @inheritDoc
     * @return Schema
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "borderRadius:i?" => [
                "description" => t("Category Follow Button border radius"),
                "x-control" => SchemaForm::textBox(
                    new FormOptions(
                        t("Border Radius"),
                        t("Set border radius for the button."),
                        t("Style Guide default.")
                    ),
                    "number"
                ),
            ],
            "buttonColor:s?" => [
                "description" => t("Category Follow Button background color"),
                "x-control" => SchemaForm::color(
                    new FormOptions(
                        t("Button border color"),
                        t("The color for button border."),
                        t("Style Guide default.")
                    )
                ),
            ],
            "textColor:s?" => [
                "description" => t("Category Follow Button text color"),
                "x-control" => SchemaForm::color(
                    new FormOptions(
                        t("Button text color"),
                        t("The color for the button text."),
                        t("Style Guide default")
                    )
                ),
            ],
            "alignment:s" => [
                "description" => t("The alignment of the Category Follow button"),
                "default" => "end",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        t("Button Alignment"),
                        t("The follow buttons alignment within the panel its placed.")
                    ),
                    new StaticFormChoices([
                        "start" => t("Left"),
                        "center" => t("Middle"),
                        "end" => t("Right"),
                    ])
                ),
            ],
        ]);
    }
}
