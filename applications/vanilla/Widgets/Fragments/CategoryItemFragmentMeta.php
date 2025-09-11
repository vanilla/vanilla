<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

namespace Vanilla\Widgets\Fragments;

use CategoryModel;
use Garden\Schema\Schema;
use Vanilla\Forum\Widgets\CategoriesWidget;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\FragmentMeta;

/**
 * Fragment for a single category item in a list.
 */
class CategoryItemFragmentMeta extends FragmentMeta
{
    /**
     * @param CategoryModel $categoryModel
     */
    public function __construct(private CategoryModel $categoryModel)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getFragmentType(): string
    {
        return "CategoryItemFragment";
    }

    public static function getName(): string
    {
        return "Category Item";
    }

    /**
     * @inheritDoc
     */
    public function getPropSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            Schema::parse([
                "categoryItem" => $this->categoryModel->schema()->merge(Schema::parse([])),
                "imageType:s" => [
                    "enum" => ["none", "icon", "image", "background"],
                ],
            ]),
            CategoriesWidget::optionsSchema("options")
        );
    }
}
