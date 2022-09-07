<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\ImageSrcSet\ImageSrcSet;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use Vanilla\Forms\FieldMatchConditional;

/**
 * Sharable properties and methods between the CallToActionWidget and GuestCTAWidget.
 */
trait CallToActionWidgetTrait
{
    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /**
     * DI.
     *
     * @param ImageSrcSetService $imageSrcSetService
     */
    public function __construct(ImageSrcSetService $imageSrcSetService)
    {
        $this->imageSrcSetService = $imageSrcSetService;
    }

    /**
     * Get widget background image srcset.
     *
     * @param string $image
     * @return ImageSrcSet|null
     */
    protected function getImageSrcSet(string $image): ?ImageSrcSet
    {
        if ($this->imageSrcSetService) {
            return $this->imageSrcSetService->getResizedSrcSet($image);
        }
        return null;
    }

    /**
     * Get widget specific schema.
     *
     * @param bool|null $isGuestCTASchema
     * @return Schema
     */
    public static function getWidgetSpecificSchema(bool $isGuestCTASchema = false): Schema
    {
        $schema = [
            "alignment:s?" => [
                "description" => "Configure widget content alignment.",
                "enum" => ["left", "center"],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Alignment",
                        "Configure alignment of the title, description and buttons.",
                        "Center aligned by default"
                    ),
                    new StaticFormChoices(["left" => "Left Aligned", "center" => "Center Aligned"])
                ),
            ],
            "textColor:s?" => [
                "description" => "Set text color.",
                "x-control" => SchemaForm::color(
                    new FormOptions("Text Color", "Pick a  color.", "Style Guide Default"),
                    null,
                    "global-mainColors-fg"
                ),
            ],
            "borderType:s?" => [
                "enum" => ["none", "border", "shadow"],
                "description" => "Describe what type of border the widget should have.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Border Type", "Choose widget border type", "Style Guide Default"),
                    new StaticFormChoices([
                        "none" => "None",
                        "border" => "Border",
                        "shadow" => "Shadow",
                    ])
                ),
            ],
        ];

        $buttonSchema = [
            "title:s" => [
                "description" => "Set button text.",
                "default" => "Button",
            ],
            "type:s" => [
                "enum" => ["standard", "primary", "text"],
                "description" => "Describe what type of button the widget should have.",
                "default" => "primary",
            ],
        ];

        if (!$isGuestCTASchema) {
            $buttonSchema["title:s"]["x-control"] = SchemaForm::textBox(
                new FormOptions("Title", "Set a custom title.")
            );
            $buttonSchema["type:s"]["x-control"] = SchemaForm::dropDown(
                new FormOptions("Button Type", "Choose widget button type", "Style Guide Default"),
                new StaticFormChoices([
                    "standard" => "Secondary",
                    "primary" => "Primary",
                    "text" => "Text",
                ])
            );
            $buttonSchema["url:s"]["description"] = "Set button url.";
            $buttonSchema["url:s"]["x-control"] = SchemaForm::textBox(
                new FormOptions("URL", "Set a button url to be redirected to.")
            );
        } else {
            //first button
            $buttonSchema["title:s"]["default"] = "Sign In";
            $buttonSchema["type:s"]["x-control"] = SchemaForm::dropDown(
                new FormOptions("Sign In Button Type", "Choose widget button type", "Style Guide Default"),
                new StaticFormChoices([
                    "standard" => "Secondary",
                    "primary" => "Primary",
                    "text" => "Text",
                ])
            );
            $schema["button?"] = Schema::parse($buttonSchema);

            //second button
            $secondButtonSchema = $buttonSchema;
            $buttonSchema["url:s?"]["description"] = "Set button url.";
            $secondButtonSchema["title:s"]["default"] = "Register";
            $secondButtonSchema["type:s"]["default"] = "standard";
            $secondButtonSchema["type:s"]["x-control"] = SchemaForm::dropDown(
                new FormOptions("Register Button Type", "Choose widget button type", "Style Guide Default"),
                new StaticFormChoices([
                    "standard" => "Secondary",
                    "primary" => "Primary",
                    "text" => "Text",
                ])
            );

            $schema["secondButton"] = Schema::parse($secondButtonSchema);
        }

        $schema["button?"] = Schema::parse($buttonSchema)->setField(
            "x-control",
            SchemaForm::section(new FormOptions("Buttons"))
        );

        $schema["background?"] = Schema::parse([
            "color:s?" => [
                "description" => "Set widget background color.",
                "x-control" => SchemaForm::color(new FormOptions("Color", "Pick a color.", "Style Guide Default")),
            ],
            "image:s?" => [
                "description" => "Set widget background image.",
                "x-control" => SchemaForm::upload(
                    new FormOptions(
                        "Image",
                        "Custom background image.",
                        "Style Guide Default",
                        "Display will vary based on widget size and content length. If using a wide layout, we recommend a large image (minimum: 1920px wide by 480px tall)."
                    )
                ),
            ],
            "useOverlay:b?" => [
                "default" => true,
                "x-control" => SchemaForm::checkBox(
                    new FormOptions("Color Overlay"),
                    new FieldMatchConditional("background.image", Schema::parse(["type" => "string", "minLength" => 1]))
                ),
            ],
        ])->setField("x-control", SchemaForm::section(new FormOptions("Background")));

        return Schema::parse($schema);
    }
}
