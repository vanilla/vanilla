<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Utility\ArrayUtils;

/**
 * @internal
 * Stateful builder class for constructing a {@link CommentThreadStructure}. For internal use in {@link CommentThreadStructure}.
 */
class CommentThreadStructureBuilder
{
    /** @var array<int, array<int>> */
    private array $commentIDsByParentID;

    /** @var array<int, array{commentID: int, parentCommentID: int, depth: int, insertUserID: int}> */
    private array $commentsByID;

    private array $focusParentCommentIDs;

    /**
     * @param CommentThreadStructure $structure
     * @param array<array{commentID: int, parentCommentID: int, depth: int, insertUserID: int}> $commentRows
     * @param CommentThreadStructureOptions $options
     */
    public function __construct(
        private CommentThreadStructure $structure,
        private array $commentRows,
        private CommentThreadStructureOptions $options
    ) {
        $this->commentIDsByParentID = ArrayUtils::arrayColumnArrays($this->commentRows, "commentID", "parentCommentID");
        $this->commentsByID = array_column($this->commentRows, null, "commentID");
        $this->focusParentCommentIDs = $this->options->getFocusCommentParentIDs($this->commentRows);
    }

    /**
     * @param int|null $rootCommentID
     *
     * @return array<CommentThreadStructureComment|CommentThreadStructureHole>
     */
    public function buildStructure(int|null $rootCommentID): array
    {
        return $this->prepareCommentChildren($rootCommentID);
    }

    /**
     * @param int|null $parentCommentID
     * @return array<CommentThreadStructureComment|CommentThreadStructureHole>
     */
    private function prepareCommentChildren(int|null $parentCommentID): array
    {
        $commentIDs = $this->commentIDsByParentID[$parentCommentID] ?? [];

        /**
         * {@link $commentIDs} Are always of the same depth.
         * {@link self::focusParentCommentIDs} are always of different depths.
         *
         * As a result only 1 could ever intersect.
         */
        $foundFocusedParentID = array_values(array_intersect($commentIDs, $this->focusParentCommentIDs))[0] ?? null;
        if ($foundFocusedParentID !== null) {
            $indexOfFoundParent = array_search($foundFocusedParentID, $commentIDs);
            // If we're the first this will be an empty array.
            $firstSetOfCommentIDs = array_slice($commentIDs, offset: 0, length: $indexOfFoundParent);
            // If we're the last comment this will be an empty array.
            $secondSetOfCommentIDs = array_slice($commentIDs, offset: $indexOfFoundParent + 1);

            $result = [];
            if (!empty($firstSetOfCommentIDs)) {
                $result = array_merge(
                    $result,
                    $this->prepareSpecificCommentChildren($parentCommentID, $firstSetOfCommentIDs, offsetAdjust: 0)
                );
            }

            $result = array_merge(
                $result,
                $this->prepareSpecificCommentChildren(
                    $parentCommentID,
                    [$foundFocusedParentID],
                    offsetAdjust: count($result)
                )
            );
            if (!empty($secondSetOfCommentIDs)) {
                $result = array_merge(
                    $result,
                    $this->prepareSpecificCommentChildren(
                        $parentCommentID,
                        $secondSetOfCommentIDs,
                        offsetAdjust: count($result)
                    )
                );
            }
            return $result;
        } else {
            return $this->prepareSpecificCommentChildren($parentCommentID, $commentIDs);
        }
    }

    /**
     * @param int|null $parentCommentID
     * @param array $commentIDs
     * @param int $offsetAdjust If we are preparing multiple specific comment children, our offset will need to be adjusted relative to our slice of commentIDs.
     * @return array<CommentThreadStructureComment|CommentThreadStructureHole>
     */
    private function prepareSpecificCommentChildren(
        int|null $parentCommentID,
        array $commentIDs,
        int $offsetAdjust = 0
    ): array {
        $results = [];
        foreach ($commentIDs as $i => $commentID) {
            $parentCommentOffset = $offsetAdjust + $i;
            $comment = $this->commentsByID[$commentID] ?? null;
            if ($comment === null) {
                // This will typically indicate a recursive comment structure.
                // Shouldn't ever happen with the data is queried.
                continue;
            }

            $isDepth1 = $comment["depth"] <= 1;
            $isMiddleDepthWithinOffset =
                $comment["depth"] < $this->options->collapseChildDepth &&
                $parentCommentOffset < $this->options->collapseChildLimit;
            $isFocusedOrParentFocused =
                in_array($commentID, $this->focusParentCommentIDs) || $commentID === $this->options->focusCommentID;

            if ($isDepth1 || $isMiddleDepthWithinOffset || $isFocusedOrParentFocused) {
                // We include all depth 1 comments and the first 3 depth 2 comments.
                $structureComment = new CommentThreadStructureComment(
                    $commentID,
                    $comment["parentCommentID"],
                    $comment["depth"],
                    $this->prepareCommentChildren($commentID)
                );
                $this->structure->structureComments[] = $structureComment;
                $results[] = $structureComment;
            } else {
                // Otherwise we are constructing holes.
                $hole = $this->gatherHole($parentCommentID, $parentCommentOffset, holeLength: count($commentIDs) - $i);

                if ($hole !== null && $hole->countAllComments > 0) {
                    $this->structure->structureHoles[] = $hole;
                    $results[] = $hole;
                }

                // The hole is the last thing.
                break;
            }

            // Remove the comment from the list of comments to process.
            // This way we don't infinitely recurse.
            unset($this->commentsByID[$commentID]);
        }

        return $results;
    }

    /**
     * @param int $parentCommentID
     * @param int $parentCommentOffset
     * @param int|null $holeLength
     * @return CommentThreadStructureHole|null
     */
    private function gatherHole(
        int $parentCommentID,
        int $parentCommentOffset,
        int|null $holeLength = null
    ): CommentThreadStructureHole|null {
        $parentComment = $this->commentsByID[$parentCommentID] ?? null;
        if ($parentComment === null) {
            return null;
        }

        $validChildIDs = $this->commentIDsByParentID[$parentCommentID] ?? [];
        $validChildIDs = array_slice($validChildIDs, offset: $parentCommentOffset, length: $holeLength);

        // Gather the child holes.
        $childHoles = [];
        foreach ($validChildIDs as $partialOffset => $childCommentID) {
            $childHoles[] = $this->gatherHole($childCommentID, $parentCommentOffset + $partialOffset);
        }

        $countAllChildren = count($validChildIDs);
        $userIDs = [];
        foreach ($validChildIDs as $childCommentID) {
            $childComment = $this->commentsByID[$childCommentID] ?? null;
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
    }
}
