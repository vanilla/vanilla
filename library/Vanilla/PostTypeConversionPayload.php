<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla;

use CategoryModel;
use DiscussionModel;
use Garden\Web\Exception\ClientException;
use Gdn;
use Vanilla\Forum\Models\PostTypeModel;

class PostTypeConversionPayload
{
    public function __construct(
        /** A raw row from GDN_Discussion (the post being converted) */
        public array $discussionRow,
        public array $targetCategoryRow,
        public string|null $fromBaseType,
        public string|null $fromPostTypeID,
        public string $toBaseType,
        public string $toPostTypeID,
        public array|null $postMeta = null
    ) {
    }

    public static function fromDiscussionLegacy(
        array $discussionRow,
        string $toLegacyPostType
    ): PostTypeConversionPayload {
        $toPostTypeID = strtolower($toLegacyPostType);
        return self::fromDiscussion($discussionRow, $toPostTypeID, []);
    }

    public static function fromDiscussion(
        array $discussionRow,
        string $toPostTypeID,
        array $postMeta
    ): PostTypeConversionPayload {
        $postTypeModel = self::postTypeModel();

        $fromPostTypeID = self::extractFromPostTypeID($discussionRow);
        $fromPostType = $postTypeModel->getByID($fromPostTypeID);
        $toPostType = $postTypeModel->getByID($toPostTypeID);
        if (!$toPostType) {
            throw new ClientException("Post type '{$toPostTypeID}' not found.");
        }
        $categoryID = $discussionRow["CategoryID"] ?? CategoryModel::ROOT_ID;
        $targetCategoryRow = CategoryModel::categories($categoryID);
        $payload = new PostTypeConversionPayload(
            discussionRow: $discussionRow,
            targetCategoryRow: $targetCategoryRow,
            fromBaseType: $fromPostType["baseType"] ?? null,
            fromPostTypeID: $fromPostType["postTypeID"] ?? null,
            toBaseType: $toPostType["baseType"],
            toPostTypeID: $toPostType["postTypeID"],
            postMeta: $postMeta
        );
        return $payload;
    }

    private static function extractFromPostTypeID(array $discussionRow): string
    {
        if (isset($discussionRow["postTypeID"])) {
            return $discussionRow["postTypeID"];
        }

        $type = $discussionRow["Type"] ?? "";
        if (empty($type)) {
            $type = DiscussionModel::DISCUSSION_TYPE;
        }

        return strtolower($type);
    }

    private static function postTypeModel(): PostTypeModel
    {
        return \Gdn::getContainer()->get(PostTypeModel::class);
    }

    /**
     * @return bool
     */
    public function hasTypeChange(): bool
    {
        return $this->fromPostTypeID !== $this->toPostTypeID;
    }

    /**
     * @return string
     */
    public function getLegacyFromType(): string
    {
        return ucfirst($this->fromBaseType);
    }

    /**
     * @return string
     */
    public function getLegacyToType(): string
    {
        return ucfirst($this->toBaseType);
    }
}
