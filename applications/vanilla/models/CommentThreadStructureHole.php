<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Dashboard\Models\UserFragment;

/**
 * Class representing a part of a {@link CommentThreadStructure} that hasn't been loaded yet.
 *
 * Use the `apiUrl` field to fetch the comments inside the hole.
 */
class CommentThreadStructureHole implements \JsonSerializable
{
    /** @var array<UserFragment> */
    public array $userFragments = [];

    private string|null $apiUrl = null;

    public function __construct(
        public int $parentCommentID,
        public int $offset,
        public int $depth,
        public array $userIDs,
        public int $countAllComments,
        public int $countAllUsers
    ) {
    }

    /**
     * @param array<int, UserFragment> $userFragmentsByID
     * @return void
     */
    public function applyUserFragments(array $userFragmentsByID): void
    {
        foreach ($this->userIDs as $userID) {
            $fragment = $userFragmentsByID[$userID] ?? null;
            if ($fragment !== null) {
                $this->userFragments[] = $fragment;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        $result = [
            "type" => "hole",
            "parentCommentID" => $this->parentCommentID,
            "offset" => $this->offset,
            "depth" => $this->depth,
            "insertUsers" => $this->userFragments,
            "countAllComments" => $this->countAllComments,
            "countAllInsertUsers" => $this->countAllUsers,
        ];

        if ($this->apiUrl !== null) {
            $result["apiUrl"] = $this->apiUrl;
        }
        return $result;
    }

    /**
     * @param int $offset
     * @return void
     */
    public function offsetDepth(int $offset): void
    {
        $this->depth += $offset;
    }

    /**
     * Apply an apiUrl to fetch the thread inside the hole.
     *
     * @param string $baseUrl
     * @param array $query
     *
     * @return void
     */
    public function applyApiUrl(string $baseUrl, array $query): void
    {
        $limit = $query["limit"];
        $page = floor($this->offset / $limit) + 1;

        $query = array_merge($query, [
            "parentCommentID" => $this->parentCommentID,
            "page" => $page,
        ]);

        $this->apiUrl = $baseUrl . "?" . http_build_query($query);
    }
}
