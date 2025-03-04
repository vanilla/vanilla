<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class PostMetaAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use HomeWidgetContainerSchemaTrait;

    /** @var InternalClient */
    private InternalClient $internalClient;

    /**
     * @param InternalClient $internalClient
     */
    public function __construct(InternalClient $internalClient)
    {
        $this->internalClient = $internalClient;
    }
    public function getProps(): ?array
    {
        if (!FeatureFlagHelper::featureEnabled("customLayout.createPost")) {
            return null;
        }
        $discussion = $this->getHydrateParam("discussion");
        $discussionPostFields = $discussion["postFields"];
        $postFieldConfigs = $this->internalClient->get("/post-fields", ["isActive" => true])->getBody();

        $postFields = [];
        foreach ($discussionPostFields as $value) {
            $postFieldConfig = array_find(
                $postFieldConfigs,
                fn($postFieldConfig) => $postFieldConfig["postFieldID"] === $value["postFieldID"]
            );
            if (!$postFieldConfig) {
                continue;
            }
            $postFields[] = [
                "postFieldID" => $postFieldConfig["postFieldID"],
                "label" => $postFieldConfig["label"],
                "description" => $postFieldConfig["description"],
                "dataType" => $postFieldConfig["dataType"],
                "visibility" => $postFieldConfig["visibility"],
                "value" => $value,
            ];
        }

        return [
            "postFields" => $postFields,
        ] + $this->props;
    }

    public function renderSeoHtml(array $props): ?string
    {
        return "";
    }

    public static function getComponentName(): string
    {
        return "PostMetaAsset";
    }

    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/postMetaAsset.svg";
    }

    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(allowDynamic: false),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(allowDynamic: false),
            self::displayOptionsSchema(),
            self::containerOptionsSchema("containerOptions", minimalProperties: true, visualBackgroundType: "outer")
        );
    }

    public static function getWidgetName(): string
    {
        return "Post Meta";
    }

    public static function getWidgetID(): string
    {
        return "asset.postMeta";
    }

    private static function displayOptionsSchema(string $fieldName = "displayOptions"): Schema
    {
        $propertiesSchema = Schema::parse([
            "asMetas?" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => SchemaForm::toggle(
                    new FormOptions("As Meta List", "Display post fields as a meta list.", "")
                ),
            ],
        ]);

        return Schema::parse([
            "displayOptions?" => $propertiesSchema
                ->setDescription("Configure the display options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Display Options"))),
        ]);
    }
}
