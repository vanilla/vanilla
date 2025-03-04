<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

class CreatePostFormAsset extends AbstractLayoutAsset implements HydrateAwareInterface
{
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;

    public function __construct(protected InternalClient $internalClient)
    {
    }

    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $template = <<<TWIG
<div>
We'll put something here eventually.
</div>
TWIG;

        return $this->renderTwigFromString($template, $props);
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "CreatePostFormAsset";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/newPostForm.svg";
    }

    public function getProps(): ?array
    {
        $hydrateParams = $this->getHydrateParams();
        $categorySlug = $hydrateParams["parentRecordID"] ?? null;
        $categoryModel = \Gdn::getContainer()->get(\CategoryModel::class);
        $categoryID = $categoryModel->ensureCategoryID($categorySlug) ?? -1;
        $category = null;
        if (!empty($categoryID)) {
            try {
                $category = $this->internalClient->get("/categories/{$categoryID}")->getBody();
            } catch (\Exception $e) {
                // No category, leave it as null.
            }
        }
        $postTypeID = $hydrateParams["postTypeID"] ?? ($hydrateParams["postType"] ?? null);
        $postTypeModel = \Gdn::getContainer()->get(PostTypeModel::class);
        $allowedPostTypes = $category ? $postTypeModel->getAllowedPostTypesByCategory($category) : [];
        $postType = null;
        if (isset($postTypeID)) {
            $postType = array_column($allowedPostTypes, null, "postTypeID")[$postTypeID];
        }

        return ["category" => $category, "postTypeID" => $postTypeID, "postType" => $postType] + $this->props;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(defaultValue: "New Post", allowDynamic: false),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(allowDynamic: false),
            Schema::parse([
                "apiParams?" => [],
            ]),
            self::containerOptionsSchema(
                "containerOptions",
                minimalProperties: true,
                visualBackgroundType: "inner",
                defaultBorderType: "separator"
            )
        )->setField("properties.containerOptions.properties.borderType.default", "none");
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Create Post Form";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "asset.createPostForm";
    }
}
