<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\RecordStatusLogModel;
use Vanilla\Utility\ModelUtils;

/**
 * Class DiscussionExpandSchema
 */
class DiscussionExpandSchema
{
    /** @var CategoryModel */
    private $categoryModel;

    /** @var TagModel */
    private $tagModel;

    /** @var RecordStatusLogModel */
    private $recordStatusLogModel;

    /**
     * DiscussionsSchema constructor.
     *
     * @param CategoryModel $categoryModel
     * @param TagModel $tagModel
     * @param RecordStatusLogModel $recordStatusLogModel
     */
    public function __construct(
        CategoryModel $categoryModel,
        TagModel $tagModel,
        RecordStatusLogModel $recordStatusLogModel
    ) {
        $this->categoryModel = $categoryModel;
        $this->tagModel = $tagModel;
        $this->recordStatusLogModel = $recordStatusLogModel;
    }

    /**
     * Get common expand schema
     * @return Schema
     */
    public static function commonExpandSchema(): Schema
    {
        return Schema::parse([
            "expand?" => self::commonExpandDefinition(),
        ]);
    }

    /**
     * Get common expand definition.
     *
     * @return Schema
     */
    public static function commonExpandDefinition(): Schema
    {
        return ApiUtils::getExpandDefinition([
            "category",
            "insertUser",
            "-insertUser",
            "lastUser",
            "lastPost",
            "lastPost.body",
            "-lastUser",
            "lastPost.insertUser",
            "raw",
            "tagIDs",
            "tags",
            "breadcrumbs",
            "-body",
            "excerpt",
            "status",
        ]);
    }

    /**
     * Common Expandable.
     *
     * @param array $rows
     * @param array|bool $expandOption
     */
    public function commonExpand(array &$rows, $expandOption)
    {
        if (ModelUtils::isExpandOption("category", $expandOption)) {
            $this->categoryModel->expandCategories($rows);
        }
        if (ModelUtils::isExpandOption("tagIDs", $expandOption)) {
            $this->tagModel->expandTagIDs($rows);
        }
        if (ModelUtils::isExpandOption("status", $expandOption)) {
            $this->recordStatusLogModel->expandDiscussionsStatuses($rows);
        }
    }
}
