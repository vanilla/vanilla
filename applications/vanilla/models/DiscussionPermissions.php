<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Permissions;
use Vanilla\Utility\ArrayUtils;

/**
 * Class implementing fetching of a set of discussion permissions.
 */
class DiscussionPermissions
{
    const COMMENTS_ADD = "comments.add";
    const COMMENTS_DELETE = "comments.delete";
    const COMMENTS_EDIT = "comments.edit";
    const ADD = "discussions.add";
    const ANNOUCE = "discussions.announce";
    const CLOSE = "discussions.close";
    const DELETE = "discussions.delete";
    const EDIT = "discussions.edit";
    const SINK = "discussions.sink";
    const VIEW = "discussions.view";
    const MODERATE = "posts.moderate";

    /**
     * DI.
     *
     * @param \Gdn_Session $session
     */
    public function __construct(protected \Gdn_Session $session)
    {
    }

    /**
     * Get all permission names.
     *
     * @return string[]
     */
    public static function getPermissionNames(): array
    {
        return [
            self::COMMENTS_ADD,
            self::COMMENTS_DELETE,
            self::COMMENTS_EDIT,
            self::ADD,
            self::ANNOUCE,
            self::CLOSE,
            self::DELETE,
            self::EDIT,
            self::SINK,
            self::VIEW,
            self::MODERATE,
        ];
    }

    /**
     * Expand permissions on discussion rows.
     *
     * @param array $discussionApiRecordOrRecords
     */
    public function expandPermissions(array &$discussionApiRecordOrRecords): void
    {
        if (ArrayUtils::isAssociative($discussionApiRecordOrRecords)) {
            $discussionApiRecords = [&$discussionApiRecordOrRecords];
        } else {
            $discussionApiRecords = &$discussionApiRecordOrRecords;
        }

        foreach ($discussionApiRecords as &$discussionApiRecord) {
            $categoryID = $discussionApiRecord["categoryID"];
            $discussionApiRecord["permissions"] = $this->getForCategory($categoryID);
        }
    }

    /**
     * Get permissions for a single category.
     *
     * @param int $categoryID
     * @return array
     */
    public function getForCategory(int $categoryID): array
    {
        $result = [];
        foreach ($this->getPermissionNames() as $permissionName) {
            $result[$permissionName] = $this->session
                ->getPermissions()
                ->has(
                    $permissionName,
                    $categoryID,
                    Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                    \CategoryModel::PERM_JUNCTION_TABLE
                );
        }
        return $result;
    }
}
