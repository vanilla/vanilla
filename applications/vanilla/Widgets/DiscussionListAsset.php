<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Gdn;
use Vanilla\Community\BaseDiscussionWidgetModule;
use Vanilla\Forum\Widgets\DiscussionsWidgetSchemaTrait;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\WidgetSchemaTrait;

/**
 * Asset representing discussion list for the page.
 */
class DiscussionListAsset extends AbstractLayoutAsset
{
    use HomeWidgetContainerSchemaTrait, WidgetSchemaTrait, DiscussionsWidgetSchemaTrait;

    /** @var BaseDiscussionWidgetModule */
    private $baseDiscussionWidget;

    /**
     * DI.
     *
     * @param BaseDiscussionWidgetModule $baseDiscussionWidget
     */
    public function __construct(BaseDiscussionWidgetModule $baseDiscussionWidget)
    {
        $this->baseDiscussionWidget = $baseDiscussionWidget;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "DiscussionsWidget";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Discussion List";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "asset.discussionList";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        $params = $this->props;
        $props = $this->baseDiscussionWidget->getProps($params);

        //at this point we should have some defaults
        if (!$props) {
            return null;
        }

        $props["noCheckboxes"] = false;
        $props["isAsset"] = true;
        $categoryFollowing = Gdn::config()->get("Vanilla.EnableCategoryFollowing", 0);
        $props["categoryFollowEnabled"] = $categoryFollowing && $categoryFollowing !== "0";
        return $props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $apiSchema = BaseDiscussionWidgetModule::getApiSchema();

        $apiSchema = $apiSchema->merge(
            SchemaUtils::composeSchemas(
                self::followedCategorySchema(),
                static::categorySchema(),
                self::siteSectionIDSchema(),
                self::sortSchema(),
                self::getSlotTypeSchema(),
                self::limitSchema()
            )
        );

        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            Schema::parse([
                "apiParams" => $apiSchema,
            ]),
            self::optionsSchema(),
            self::containerOptionsSchema("containerOptions")
        );

        return $schema;
    }
}
