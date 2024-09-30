<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

/**
 * Options for building a comment thread structure.
 */
final class CommentThreadStructureOptions
{
    /**
     * @param int $collapseChildDepth The depth at which we start fully collapsing child comments.
     * @param int $collapseChildLimit The number of children displayed for non-top level items less than {@link self::$collapseChildDepth}.
     * @param int|null $focusCommentID If specified this comment will be ensured to be expanded.
     *
     * @notes
     * Depths are relative to the query and start at 1. This means if we query children of a absolute depth 2 comment,
     * the absolute depth 3 comments will be at depth 1 here.
     *
     * The absolute depths are calculating after the fact with {@link CommentThreadStructure::offsetDepth()}.
     *
     * Relative depth 1 items are always fully expanded. Items at or greater than {@link self::$collapseChildDepth} are always fully collapsed into a hole.
     * Items of a relative depth in between are partially expanded, with {@link self::$collapseChildLimit} children shown.
     */
    public function __construct(
        public int $collapseChildDepth = 3,
        public int $collapseChildLimit = 3,
        public int|null $focusCommentID = null
    ) {
    }

    /**
     * Get all the parent comment IDs for the focus comment.
     *
     * @param array<array{commentID: int, parentCommentID: int, depth: int, insertUserID: int}> $rows $rows
     * @return array<int>
     */
    public function getFocusCommentParentIDs(array $rows): array
    {
        $parentIDs = [];
        $parentIDsByCommentIDs = array_column($rows, "parentCommentID", "commentID");
        $currentID = $this->focusCommentID;
        $recursionGaurd = 0;
        while ($currentID !== null) {
            $recursionGaurd++;
            if ($recursionGaurd > 10) {
                break;
            }

            $parentID = $parentIDsByCommentIDs[$currentID] ?? null;
            if ($parentID === null) {
                break;
            }

            $parentIDs[] = $parentID;
            $currentID = $parentID;
        }
        return $parentIDs;
    }
}
