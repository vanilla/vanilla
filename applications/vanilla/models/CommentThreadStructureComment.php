<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

/**
 * Item in {@link CommentThreadStructure} representing a comment record.
 *
 * May contain other {@link CommentThreadStructureComment} and {@link CommentThreadStructureHole} inside of it.
 */
class CommentThreadStructureComment implements \JsonSerializable
{
    /**
     * @param int $commentID
     * @param int|null $parentCommentID
     * @param int $depth
     * @param array<CommentThreadStructureHole|CommentThreadStructureComment> $children
     */
    public function __construct(
        public int $commentID,
        public int|null $parentCommentID,
        public int $depth,
        public array $children = []
    ) {
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            "type" => "comment",
            "commentID" => $this->commentID,
            "parentCommentID" => $this->parentCommentID,
            "depth" => $this->depth,
            "children" => $this->children,
        ];
    }

    /**
     * @param int $offset
     * @return void
     */
    public function offsetDepth(int $offset): void
    {
        $this->depth += $offset;
        foreach ($this->children as $child) {
            $child->offsetDepth($offset);
        }
    }
}
