<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use CategoryModel;
use Garden\Schema\Schema;
use Gdn_Upload;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Class BannerFullWidget
 */
class BannerFullWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;

    /** \CategoryModel */
    protected $categoryModel;
    /** ConfigurationInterface */
    private $config;

    /**
     * DI.
     *
     * @param CategoryModel $categoryModel
     * @param ConfigurationInterface $config
     */
    public function __construct(CategoryModel $categoryModel, ConfigurationInterface $config)
    {
        $this->categoryModel = $categoryModel;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Banner";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "app-banner";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/banner.svg";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "backgroundImage:s?" => [
                "description" => "URL for the background image.",
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Background Image", "URL for the background image.")
                ),
            ],
            "categoryID:s?" => "Category ID.",
            "title:s?" => [
                "description" => "Banner title.",
                "x-control" => SchemaForm::textBox(new FormOptions("Title", "Banner title.")),
            ],
            "description:s?" => [
                "description" => "Banner description.",
                "x-control" => SchemaForm::textBox(new FormOptions("Description", "Banner description.")),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getProps(): ?array
    {
        $categoryID = $this->props["categoryID"] ?? null;

        $bgUrl = $this->categoryModel->getCategoryFieldRecursive(
            $categoryID,
            "BannerImage",
            $this->config->get("Garden.BannerImage", null)
        );
        $bgUrl = $bgUrl ? Gdn_Upload::url($bgUrl) : null;

        return array_merge(
            [
                "backgroundImage" => $bgUrl,
                "options" => [
                    "enabled" => true,
                ],
            ],
            $this->props
        );
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "Banner";
    }
}
