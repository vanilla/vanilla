<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Layout\Section\SectionThreeColumns;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Navigation\NavLinkSchema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Class QuickLinksWidget
 */
class QuickLinksWidget extends AbstractReactModule implements CombinedPropsWidgetInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "QuickLinks";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Quick Links";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "quick-links";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/quicklinks.svg";
    }

    /**
     * @return array
     */
    public static function getAllowedSectionIDs(): array
    {
        return [SectionTwoColumns::getWidgetID(), SectionThreeColumns::getWidgetID()];
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        $linkSchema = new NavLinkSchema();
        return SchemaUtils::composeSchemas(
            Schema::parse([
                "title:s?" => [
                    "default" => t("Quick Links"),
                    "x-control" => SchemaForm::textBox(
                        new FormOptions("Title", "Title for the widget.", "Quick Links")
                    ),
                ],
                "links?" => [
                    "type" => "array",
                    // Currently has to be an array of garden-hydrate crashes.
                    "items" => $linkSchema->getSchemaArray(),
                    "x-control" => SchemaForm::dragAndDrop(new FormOptions("Links"), $linkSchema),
                ],
            ]),
            self::containerOptionsSchema("containerOptions", null, true)
        );
    }
}
