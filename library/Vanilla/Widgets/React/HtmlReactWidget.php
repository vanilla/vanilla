<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\ArticleJsonLD;
use Vanilla\Knowledge\Models\SearchJsonLD;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Web\ContentSecurityPolicy\ContentSecurityPolicyModel;

/**
 * Widget for rendering raw HTML in react.
 */
class HtmlReactWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface
{
    use AllSectionTrait;
    use CombinedPropsWidgetTrait;

    /** @var ContentSecurityPolicyModel */
    private $cspModel;

    /**
     * DI.
     *
     * @param ContentSecurityPolicyModel $cspModel
     */
    public function __construct(ContentSecurityPolicyModel $cspModel)
    {
        $this->cspModel = $cspModel;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "HtmlWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/customhtml.svg";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "name:s?" => [
                "type" => "string",
                "x-control" => SchemaForm::textBox(new FormOptions("Name")),
            ],
            "isAdvertisement:b" => [
                "description" => "Controls if element is advertisement, and display is controlled by permissions.",
                "default" => false,
                "x-control" => SchemaForm::toggle(new FormOptions("Advertisement")),
            ],
            "html:s?" => [
                "description" => "Sanitized HTML to render.",
            ],
            "javascript:s?" => [
                "description" => "Sanitized JavaScript to render.",
            ],
            "css:s?" => [
                "description" => "Sanitized CSS to render.",
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        if ($this->props["isAdvertisement"] && checkPermission("noAds.use")) {
            return null;
        }
        if (!empty($this->props["javascript"])) {
            $this->props["javascriptNonce"] = $this->cspModel->getNonce();
        }
        return $this->props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Custom HTML";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "html";
    }
}
