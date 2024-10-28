<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ArrayUtils;

class ExpectedThreadStructure implements \JsonSerializable
{
    private array $expected = [];

    private function __construct(private int $depth = 1)
    {
    }

    public static function create(int $initialDepth = 1): ExpectedThreadStructure
    {
        return new ExpectedThreadStructure($initialDepth);
    }

    private function setDepth(int $depth): void
    {
        $this->depth = $depth;
        foreach ($this->expected as &$expected) {
            $expected["depth"] = $depth;

            if (isset($expected["children"])) {
                $expected["children"]->setDepth($depth + 1);
            }
        }
    }

    public function comment(array $comment, ExpectedThreadStructure $children = null): static
    {
        $this->expected[] = [
            "type" => "comment",
            "commentID" => $comment["commentID"],
            "parentCommentID" => $comment["parentCommentID"] ?? null,
            "depth" => $this->depth,
            "children" => $children ?? new ExpectedThreadStructure(),
        ];
        $children?->setDepth($this->depth + 1);
        return $this;
    }

    public function hole(
        array $parentComment,
        int $countAllComments,
        int $countAllUsers,
        int|null $offset = null
    ): static {
        $this->expected[] = [
            "type" => "hole",
            "parentCommentID" => $parentComment["commentID"],
            "offset" => $offset ?? count($this->expected),
            "depth" => $this->depth,
            "countAllComments" => $countAllComments,
            "countAllInsertUsers" => $countAllUsers,
        ];
        return $this;
    }

    public function jsonSerialize()
    {
        // Make sure depths are normalized.
        $this->setDepth($this->depth);
        return $this->expected;
    }

    public function assertMatches(array $actualStructure)
    {
        $self = json_decode(json_encode($this), true);

        ArrayUtils::walkRecursiveArray($actualStructure, function (&$value) {
            unset($value["insertUsers"]);
            unset($value["apiUrl"]);
        });
        TestCase::assertSame($self, $actualStructure);
    }
}
