<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Class DiscussionExpandSchema
 */
class DiscussionExpandSchema {

    /**
     * @var CategoryModel
     */
    private $categoryModel;
    /**
     * @var TagModel
     */
    private $tagModel;

    /**
     * DiscussionsSchema constructor.
     *
     * @param CategoryModel $categoryModel
     * @param TagModel $tagModel
     */
    public function __construct(CategoryModel $categoryModel, TagModel $tagModel) {

        $this->categoryModel = $categoryModel;
        $this->tagModel = $tagModel;
    }

    /**
     * Get common expand schema
     * @return Schema
     */
    public static function commonExpandSchema(): Schema {
        return Schema::parse([
            'expand?' => self::commonExpandDefinition(),
        ]);
    }

    /**
     * Get common expand definition.
     *
     * @return Schema
     */
    public static function commonExpandDefinition(): Schema {
        return ApiUtils::getExpandDefinition([
            'category',
            'insertUser',
            '-insertUser',
            'lastUser',
            'lastPost',
            'lastPost.body',
            '-lastUser',
            'lastPost.insertUser',
            'raw',
            'tagIDs',
            'tags',
            'breadcrumbs',
            '-body',
            'excerpt',
        ]);
    }

    /**
     * Common Expandable.
     *
     * @param array $rows
     * @param array|bool $expandOption
     */
    public function commonExpand(array &$rows, $expandOption) {
        if (ModelUtils::isExpandOption('category', $expandOption)) {
            $this->categoryModel->expandCategories($rows);
        }
        if (ModelUtils::isExpandOption('tagIDs', $expandOption)) {
            $this->tagModel->expandTagIDs($rows);
        }
    }
}
