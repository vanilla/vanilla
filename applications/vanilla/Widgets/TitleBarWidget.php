<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormModalOptions;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\NoCustomFragmentCondition;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\Fragments\TitleBarFragmentMeta;
use Vanilla\Widgets\React\ReactWidget;

class TitleBarWidget extends ReactWidget
{
    public const POSITION_STATIC_TRANSPARENT = "StaticTransparent";
    public const POSITION_STATIC_SOLID = "StaticSolid";
    public const POSITION_STICKY_TRANSPARENT = "StickyTransparent";
    public const POSITION_STICKY_SOLID = "StickySolid";

    public static function getAllowedSectionIDs(): array
    {
        return ["TitleBar"];
    }

    public function renderSeoHtml(array $props): ?string
    {
        return "TODO:";
    }

    public static function getComponentName(): string
    {
        return "TitleBar";
    }

    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/titlebar.svg";
    }

    public static function getWidgetSchema(bool $forFragment = false): Schema
    {
        $schemaArr = [];

        $customFragmentCondition = new NoCustomFragmentCondition("TitleBarFragment");
        if (!$forFragment) {
            $schemaArr = array_merge($schemaArr, [
                "backgroundColor:s?" => [
                    "x-control" => SchemaForm::color(
                        new FormOptions("Background Color", placeholder: "Style Guide Default"),
                        conditions: $customFragmentCondition
                    ),
                ],
                "foregroundColor:s?" => [
                    "x-control" => SchemaForm::color(
                        new FormOptions("Foreground Color", placeholder: "Style Guide Default"),
                        conditions: $customFragmentCondition
                    ),
                ],
            ]);
        }

        $schemaArr["positioning:s?"] = [
            "enum" => [
                self::POSITION_STICKY_TRANSPARENT,
                self::POSITION_STATIC_TRANSPARENT,
                self::POSITION_STATIC_SOLID,
                self::POSITION_STICKY_SOLID,
            ],
            "default" => self::POSITION_STICKY_SOLID,
            "x-control" => SchemaForm::dropDown(
                new FormOptions("Positioning"),
                choices: new StaticFormChoices([
                    self::POSITION_STICKY_SOLID => "Sticky (Solid)",
                    self::POSITION_STICKY_TRANSPARENT => "Sticky (Transparent)",
                    self::POSITION_STATIC_SOLID => "Static (Solid)",
                    self::POSITION_STATIC_TRANSPARENT => "Static (Transparent)",
                ])
            ),
        ];

        if (!$forFragment) {
            $schemaArr = array_merge($schemaArr, [
                "height:i?" => [
                    "x-control" => SchemaForm::textBox(
                        new FormOptions("Height", placeholder: "Style Guide Default"),
                        type: "number",
                        conditions: $customFragmentCondition
                    ),
                ],
                "heightMobile:i?" => [
                    "x-control" => SchemaForm::textBox(
                        new FormOptions("Height (Mobile)", placeholder: "Style Guide Default"),
                        type: "number",
                        conditions: $customFragmentCondition
                    ),
                ],
                "borderType:s?" => [
                    "enum" => ["none", "border", "shadow"],
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Border Type", placeholder: "Style Guide Default"),
                        choices: new StaticFormChoices([
                            "none" => "None",
                            "border" => "Border",
                            "shadow" => "Shadow",
                        ]),
                        conditions: $customFragmentCondition
                    ),
                ],
            ]);
        }

        if (!$forFragment) {
            $schemaArr["logoType:s"] = [
                "enum" => ["styleguide", "custom"],
                "default" => "styleguide",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Logo Type"),
                    choices: new StaticFormChoices([
                        "styleguide" => "Style Guide Default",
                        "custom" => "Custom",
                    ])
                ),
            ];
        }

        $schemaArr["logo?"] = self::logoSchema($forFragment)->setField(
            "x-control",
            SchemaForm::section(
                new FormOptions("Custom Logo"),
                conditions: new FieldMatchConditional("logoType", new Schema(["const" => "custom"]))
            )
        );

        if (!$forFragment) {
            $schemaArr["navigationType:s"] = [
                "enum" => ["styleguide", "custom"],
                "default" => "styleguide",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Navigation Type"),
                    choices: new StaticFormChoices([
                        "styleguide" => "Style Guide Default",
                        "custom" => "Custom",
                    ])
                ),
            ];
        }

        $schemaArr["navigation?"] = self::navigationSchema()->setField(
            "x-control",
            SchemaForm::section(
                new FormOptions("Custom Navigation"),
                conditions: new FieldMatchConditional("navigationType", new Schema(["const" => "custom"]))
            )
        );

        return Schema::parse($schemaArr);
    }

    public static function logoSchema(bool $forFragment = false): Schema
    {
        $schemaArr = [];
        $customFragmentCondition = new NoCustomFragmentCondition("TitleBar");

        $schemaArr["imageUrl:s?"] = [
            "description" => "URL of the logo image (desktop).",
            "x-control" => SchemaForm::upload(new FormOptions(label: "Image")),
        ];

        if (!$forFragment) {
            $schemaArr["alignment:s?"] = [
                "enum" => ["left", "center"],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(label: "Alignment", placeholder: "Styleguide Default"),
                    choices: new StaticFormChoices([
                        "left" => "Left",
                        "center" => "Center",
                    ]),
                    conditions: $customFragmentCondition
                ),
            ];
        }

        $schemaArr["imageUrlMobile:s?"] = [
            "description" => "URL of the logo image (mobile).",
            "x-control" => SchemaForm::upload(new FormOptions(label: "Image (Mobile)")),
        ];

        if (!$forFragment) {
            $schemaArr["alignmentMobile:s?"] = [
                "enum" => ["left", "center"],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(label: "Alignment (Mobile)", placeholder: "Styleguide Default"),
                    choices: new StaticFormChoices([
                        "left" => "Left",
                        "center" => "Center",
                    ]),
                    conditions: $customFragmentCondition
                ),
            ];
        }

        $schemaArr["url:s?"] = [
            "description" => "Link used when clicking on the logo.",
            "x-control" => SchemaForm::textBox(new FormOptions("URL", placeholder: "/")),
        ];

        return Schema::parse($schemaArr);
    }

    public static function navigationSchema(bool $forFragment = false): Schema
    {
        $schemaArr = [];

        $customFragmentCondition = new NoCustomFragmentCondition("TitleBar");

        if (!$forFragment) {
            $schemaArr["alignment:s?"] = [
                "enum" => ["left", "center"],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(label: "Alignment", placeholder: "Styleguide Default"),
                    choices: new StaticFormChoices([
                        "left" => "Left",
                        "center" => "Center",
                    ]),
                    conditions: $customFragmentCondition
                ),
            ];
        }

        $schemaArr["items?"] = new Schema([
            "type" => "array",
            "items" => $forFragment ? self::navItemSchema() : null,
            "default" => [],
            "x-control" => SchemaForm::custom(new FormOptions("Links"), "NavigationLinksModalControl"),
        ]);

        return Schema::parse($schemaArr);
    }

    public static function navItemSchema(): Schema
    {
        $itemNoChildren = Schema::parse([
            "name:s" => [
                "description" => "Name of the navigation item",
                "x-control" => SchemaForm::textBox(new FormOptions("Label")),
            ],
            "url:s?" => [
                "description" => "URL of the navigation item. May be omitted if the item is only a heading.",
                "x-control" => SchemaForm::textBox(new FormOptions("URL")),
            ],
            "permission:s?" =>
                "If specified, the link will only be shown to users with this permission. Follows the format of permissions in /api/v2/users/\$me/permissions",
        ]);

        return SchemaUtils::composeSchemas(
            $itemNoChildren,
            Schema::parse([
                "children:a?" => $itemNoChildren,
            ])
        );
    }

    public static function getWidgetName(): string
    {
        return "Title Bar";
    }

    public static function getWidgetID(): string
    {
        return "titleBar";
    }

    public static function getFragmentClasses(): array
    {
        return [TitleBarFragmentMeta::class];
    }
}
