<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Dashboard\Models\UserFragment;
use Vanilla\Utility\ArrayUtils;

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
    private array $structureComments = [];

    /** @var array<CommentThreadStructureHole> */
    private array $structureHoles = [];

    /**
     * @param int|null $rootCommentID
     * @param array<array{commentID: int, parentCommentID: int, depth: int, insertUserID: int}> $rows
     * @param int $pagingCount
     */
    public function __construct(public int|null $rootCommentID, array $rows, private int $pagingCount)
    {
        $this->structure = $this->buildStructure($rows);
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
     * @param array<array{commentID: int, parentCommentID: int, depth: int, insertUserID: int}> $rows
     */
    private function buildStructure(array $rows)
    {
        $commentIDsByParentID = ArrayUtils::arrayColumnArrays($rows, "commentID", "parentCommentID");
        $commentsByID = array_column($rows, null, "commentID");

        $gatherHole = function (int $parentCommentID, int $parentCommentOffset) use (
            &$gatherHole,
            &$commentsByID,
            &$commentIDsByParentID
        ): CommentThreadStructureHole|null {
            $parentComment = $commentsByID[$parentCommentID] ?? null;
            if ($parentComment === null) {
                return null;
            }

            $validChildIDs = $commentIDsByParentID[$parentCommentID] ?? [];
            $validChildIDs = array_slice($validChildIDs, offset: $parentCommentOffset);

            // Gather the child holes.
            $childHoles = [];
            foreach ($validChildIDs as $partialOffset => $childCommentID) {
                $childHoles[] = $gatherHole($childCommentID, $parentCommentOffset + $partialOffset);
            }

            $countAllChildren = count($validChildIDs);
            $userIDs = [];
            foreach ($validChildIDs as $childCommentID) {
                $childComment = $commentsByID[$childCommentID] ?? null;
                if ($childComment === null) {
                    continue;
                }

                $userIDs[] = $childComment["insertUserID"];
            }
            $countAllUsers = count(array_unique($userIDs));
            foreach ($childHoles as $childHole) {
                if ($childHole === null) {
                    continue;
                }
                $countAllChildren += $childHole->countAllComments;
                $countAllUsers += $childHole->countAllUsers;
                $userIDs = array_merge($userIDs, $childHole->userIDs);
            }
            $first5UserIDs = array_slice(array_unique($userIDs), 0, 5);

            return new CommentThreadStructureHole(
                parentCommentID: $parentCommentID,
                offset: $parentCommentOffset,
                depth: $parentComment["depth"] + 1,
                userIDs: $first5UserIDs,
                countAllComments: $countAllChildren,
                countAllUsers: $countAllUsers
            );
        };

        $prepareStructure = function (int|null $parentCommentID) use (
            &$prepareStructure,
            &$commentsByID,
            &$commentIDsByParentID,
            &$gatherHole
        ): array {
            $results = [];

            $commentIDs = $commentIDsByParentID[$parentCommentID] ?? [];
            foreach ($commentIDs as $parentCommentOffset => $commentID) {
                $comment = $commentsByID[$commentID] ?? null;
                if ($comment === null) {
                    // This will typically indicate a recursive comment structure.
                    // Shouldn't ever happen with the data is queried.
                    continue;
                }

                if ($comment["depth"] <= 1 || ($comment["depth"] === 2 && $parentCommentOffset < 3)) {
                    // We include all depth 1 comments and the first 3 depth 2 comments.
                    $structureComment = new CommentThreadStructureComment(
                        $commentID,
                        $comment["parentCommentID"],
                        $comment["depth"],
                        $prepareStructure($commentID)
                    );
                    $this->structureComments[] = $structureComment;
                    $results[] = $structureComment;
                } else {
                    // Otherwise we are constructing holes.
                    $hole = $gatherHole($parentCommentID, $parentCommentOffset);

                    if ($hole !== null && $hole->countAllComments > 0) {
                        $this->structureHoles[] = $hole;
                        $results[] = $hole;
                    }

                    // The hole is the last thing.
                    break;
                }

                // Remove the comment from the list of comments to process.
                // This way we don't infinitely recurse.
                unset($commentsByID[$commentID]);
            }

            return $results;
        };

        $structure = $prepareStructure($this->rootCommentID);
        return $structure;
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
