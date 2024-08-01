<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Methods for writing tests for the reactions plugin.
 */
trait ReactionsTestTrait
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * React to a post through the /react/{postType}/{reactionType} endpoint.
     *
     * @param string $recordType
     * @param int $recordID
     * @param string $reactionName
     * @return void
     */
    private function react(string $recordType, int $recordID, string $reactionName): void
    {
        $this->bessy()->postJsonData("/react/$recordType/$reactionName?id={$recordID}");
    }

    /**
     * Create a user with the permissions to react to posts.
     *
     * @param int|null $categoryID
     * @return array
     */
    private function createReactingUser(?int $categoryID = null): array
    {
        $categoryPermissions = isset($categoryID)
            ? ($categoryPermissions = [
                [
                    "type" => "category",
                    "id" => $categoryID,
                    "permissions" => [
                        "discussions.view" => true,
                        "discussions.add" => true,
                        "comments.add" => true,
                    ],
                ],
            ])
            : [];

        $permissions = array_merge(
            [
                [
                    "type" => "global",
                    "permissions" => [
                        "reactions.positive.add" => true,
                    ],
                ],
            ],
            $categoryPermissions
        );

        $role = $this->createRole([
            "name" => "reactRole",
            "permissions" => $permissions,
        ]);
        $user = $this->createUser([
            "roleID" => [\RoleModel::MEMBER_ID, $role["roleID"]],
        ]);

        return $user;
    }

    /**
     * Get the reaction data from a post.
     *
     * @param array $post
     * @param string|null $reactionCode
     * @return array
     */
    private function getPostReaction(array $post, ?string $reactionCode): array
    {
        if (!isset($post["reactions"])) {
            return [];
        }

        $allReactionsByCode = array_column($post["reactions"], null, "urlcode");

        if ($reactionCode) {
            return $allReactionsByCode[$reactionCode];
        } else {
            return $allReactionsByCode;
        }
    }
}
