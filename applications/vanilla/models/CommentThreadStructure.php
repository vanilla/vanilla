<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Dashboard\Models\UserFragment;

/**
 * Class representing part of a comment thread structure.
 *
 * The thread structure is made of {@link CommentThreadStructureComment} and {@link CommentThreadStructureHole}.
 */
class CommentThreadStructure implements \JsonSerializable
{
    /** @var array<CommentThreadStructureHole|CommentThreadStructureComment>  */
    private array $structure;

    /** @var array<CommentThreadStructureComment> */
    public array $structureComments = [];

    /** @var array<CommentThreadStructureHole> */
    public array $structureHoles = [];

    /**
     * @param int|null $rootCommentID
     * @param array<array{commentID: int, parentCommentID: int, depth: int, insertUserID: int}> $rows
     * @param int $pagingCount
     * @param CommentThreadStructureOptions $options
     */
    public function __construct(
        public int|null $rootCommentID,
        array $rows,
        private int $pagingCount,
        CommentThreadStructureOptions $options
    ) {
        $builder = new CommentThreadStructureBuilder($this, $rows, $options);
        $this->structure = $builder->buildStructure($this->rootCommentID);
    }

    /**
     * @return int
     */
    public function getPagingCount(): int
    {
        return $this->pagingCount;
    }

    /**
     * @return int
     */
    public function getCountTopLevelComments(): int
    {
        return count($this->structure);
    }

    /**
     * @return int[]
     */
    public function getPreloadCommentIDs(): array
    {
        $commentIDs = [];
        foreach ($this->structureComments as $comment) {
            $commentIDs[] = $comment->commentID;
        }
        return $commentIDs;
    }

    /**
     * @return int[]
     */
    public function getHolePreloadUserIDs(): array
    {
        $userIDs = [];
        foreach ($this->structureHoles as $hole) {
            $userIDs = array_merge($userIDs, $hole->userIDs);
        }
        return array_unique($userIDs);
    }

    /**
     * @param array<int, UserFragment> $userFragmentsByID
     * @return void
     */
    public function applyHoleUserFragments(array $userFragmentsByID): void
    {
        foreach ($this->structureHoles as $hole) {
            $hole->applyUserFragments($userFragmentsByID);
        }
    }

    /**
     * @param CommentThreadStructureHole|CommentThreadStructureComment $item
     * @return void
     */
    public function appendItem(CommentThreadStructureHole|CommentThreadStructureComment $item): void
    {
        $this->structure[] = $item;
        if ($item instanceof CommentThreadStructureComment) {
            $this->structureComments[] = $item;
        } elseif ($item instanceof CommentThreadStructureHole) {
            $this->structureHoles[] = $item;
        }
    }

    /**
     * @return array<CommentThreadStructureComment|CommentThreadStructureHole>
     */
    public function jsonSerialize(): array
    {
        return $this->structure;
    }

    /**
     * Sometimes a structure is digging deeper into an already selected thread. In these cases we need to offset our depth.
     *
     * @param int $offset
     *
     * @return void
     */
    public function offsetDepth(int $offset): void
    {
        if ($offset === 0) {
            // Nothing to do.
            return;
        }
        foreach ($this->structure as $item) {
            $item->offsetDepth($offset);
        }
    }

    /**
     * Given a particular query on the API ensure all holes have and `apiUrl` field to fetch their contents.
     *
     * @param string $baseUrl
     * @param array $query
     * @return void
     */
    public function applyApiUrlsToHoles(string $baseUrl, array $query): void
    {
        foreach ($this->structureHoles as $hole) {
            $hole->applyApiUrl($baseUrl, $query);
        }
    }
}
