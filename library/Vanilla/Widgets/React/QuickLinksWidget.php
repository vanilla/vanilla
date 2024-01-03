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
use Vanilla\Layout\Section\SectionThreeColumnsEven;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Layout\Section\SectionTwoColumnsEven;
use Vanilla\Navigation\NavLinkSchema;
use Vanilla\Theme\VariableProviders\QuickLink;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
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

    private \Gdn_Session $session;

    private QuickLinksVariableProvider $quickLinkProvider;

    /**
     * @param \Gdn_Session $session
     * @param QuickLinksVariableProvider $quickLinkProvider
     */
    public function __construct(\Gdn_Session $session, QuickLinksVariableProvider $quickLinkProvider)
    {
        parent::__construct();
        $this->session = $session;
        $this->quickLinkProvider = $quickLinkProvider;
    }

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
        return [
            SectionTwoColumns::getWidgetID(),
            SectionThreeColumns::getWidgetID(),
            SectionThreeColumnsEven::getWidgetID(),
            SectionTwoColumnsEven::getWidgetID(),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        $linkSchema = new NavLinkSchema();
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(null, false, t("Quick Links")),
            Schema::parse([
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

    /**
     * @inheritDoc
     */
    public function getProps(): ?array
    {
        $props = $this->props;
        if (empty($props["links"])) {
            // Apply defaults
            $providerLinks = $this->quickLinkProvider->getAllLinks();

            $props["links"] = array_map(function (QuickLink $link): array {
                return $link->jsonSerialize();
            }, $providerLinks);
        }
        return $props;
    }

    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        // Notably these links are empty unless explicitly configured. In the future we may want
        // to move the default values of these from the frontned into the backend.
        $linksFiltered = array_filter($props["links"] ?? [], function (array $link) {
            if (!empty($link["permission"]) && !$this->session->checkPermission($link["permission"])) {
                return false;
            }

            if ($link["isHidden"] ?? false) {
                return false;
            }

            return true;
        });
        $result = $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($linksFiltered));
        return $result;
    }
}
