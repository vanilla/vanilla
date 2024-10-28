<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\RecordStatusLogModel;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Forum\Models\PostMetaModel;
use Vanilla\Permissions;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\PermissionCheckTrait;

/**
 * Class DiscussionExpandSchema
 */
class DiscussionExpandSchema
{
    /**
     * DI.
     */
    public function __construct(
        private CategoryModel $categoryModel,
        private TagModel $tagModel,
        private RecordStatusLogModel $recordStatusLogModel,
        private RecordStatusModel $recordStatusModel,
        private AttachmentModel $attachmentModel,
        private ReactionModel $reactionModel,
        private \Vanilla\Forum\Models\CommunityManagement\ReportModel $reportModel,
        private Gdn_Session $session,
        private PostMetaModel $postMetaModel
    ) {
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
            "snippet",
            "status",
            "status.log",
            "reactions",
            "attachments",
            "reportMeta",
            "countReports",
            "postFields",
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
            $this->recordStatusModel->expandStatuses($rows);
        }
        if (ModelUtils::isExpandOption("attachments", $expandOption)) {
            $this->attachmentModel->joinAttachments($rows);
        }

        $permissions = $this->session->getPermissions();
        $hasReportViewPermission = $permissions->hasAny(["posts.moderate", "community.moderate"]);
        if (ModelUtils::isExpandOption("reportMeta", $expandOption) && $hasReportViewPermission) {
            $this->reportModel->expandReportMeta($rows, "discussion");
        }

        // This one can be slightly performance intensive so don't do it unless explicitly asked.
        // Eg. Will not be included in expand=all
        if (ModelUtils::isExpandOption("status.log", $expandOption, true)) {
            if (!ModelUtils::isExpandOption("status", $expandOption)) {
                $this->recordStatusModel->expandStatuses($rows);
            }

            $this->recordStatusLogModel->expandStatusLogs($rows, "discussion", "discussionID");
        }

        if (ModelUtils::isExpandOption("reactions", $expandOption)) {
            $this->reactionModel->expandDiscussionReactions($rows);
        }

        if (ModelUtils::isExpandOption("postFields", $expandOption)) {
            $this->postMetaModel->joinPostFields($rows);
        }
    }
}
