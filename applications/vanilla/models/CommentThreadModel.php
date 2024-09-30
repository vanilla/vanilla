<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Database\Select;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Models\Model;
use Webmozart\Assert\Assert;

/**
 * Model for calculating comment threads.
 */
class CommentThreadModel
{
    /**
     * DI.
     */
    public function __construct(private \Gdn_Database $db, private \UserModel $userModel)
    {
    }

    /**
     * Select a paging count for a comment thread.
     * Notably only the top level items are counted.
     *
     * @param array $where
     * @param array $options
     *
     * @return int
     */
    public function selectPagingCount(array $where, array $options): int
    {
        $filteredInitialQuery = $this->db
            ->createSql()
            ->select("CommentID as commentID")
            ->from("Comment")
            ->where($where)
            ->applyModelOptions($options);

        $count = $this->db
            ->createSql()
            ->select("COUNT(*) as count")
            ->fromSubquery("c1", $filteredInitialQuery)
            ->get()
            ->value("count");

        return $count;
    }

    /**
     * Select comments from a given query including children (recursively) and return a structure.
     * This does not select out full comment records, only the structure.
     *
     * @param array $where
     * @param array $options
     *
     * @return CommentThreadStructure
     */
    public function selectCommentThreadStructure(array $where, array $options): CommentThreadStructure
    {
        $parentCommentID = $where["parentCommentID"] ?? null;
        $pagingCount = $this->selectPagingCount($where, [
            Model::OPT_ORDER => $options[Model::OPT_ORDER] ?? null,
            Model::OPT_LIMIT => null,
        ]);

        $depthOffset = 0;
        if ($parentCommentID !== null) {
            $parentComment = $this->selectThreadCommentFragment($parentCommentID);
            if (
                $parentComment["parentRecordType"] !== $where["parentRecordType"] ||
                $parentComment["parentRecordID"] !== $where["parentRecordID"]
            ) {
                throw new ClientException("Parent comment is part of a discussion thread.", [
                    "expected" => [
                        "parentRecordType" => $parentComment["parentRecordType"],
                        "parentRecordID" => $parentComment["parentRecordID"],
                    ],
                ]);
            }
            $depthOffset = $parentComment["depth"];
        }

        $offset = $options[Model::OPT_OFFSET] ?? 0;
        $limit = $options[Model::OPT_LIMIT] ?? 0;

        Assert::greaterThan($limit, 0, "Limit must be greater than 0");

        if ($offset > $pagingCount) {
            throw new NotFoundException("Page");
        }

        $rows = $this->createRecursiveCommentQuery($where, $options)
            ->join("Comment cu", "ct.CommentID = cu.CommentID", "left")
            ->select("ct.commentID")
            ->select("ct.parentCommentID")
            ->select("ct.depth")
            ->select("cu.InsertUserID as insertUserID")
            ->get()
            ->resultArray();

        $threadStructure = new CommentThreadStructure($parentCommentID, $rows, $pagingCount);

        // If we selected from a parent comment we need to make sure any further pages are reflected as a hole.
        if ($parentCommentID !== null && $offset + $threadStructure->getCountTopLevelComments() < $pagingCount) {
            // We had a parent commentID and there are more comments to load after us.
            // We'll have our standard pagination headers but for simplicity let's create hole at the end of the structure.
            $holeOffset = $offset + $limit;
            $holeQuery = $this->createRecursiveCommentQuery(
                $where,
                array_merge($options, [
                    Model::OPT_OFFSET => $offset + $limit,
                    Model::OPT_LIMIT => 100000, // Notably no limit here.
                ])
            )
                ->join("Comment cu", "ct.CommentID = cu.CommentID", "left")
                ->select("ct.CommentID", "COUNT", "holeCount")
                ->select("cu.InsertUserID", "GROUP_CONCAT", "holeInsertUserIDs");
            $holeResult = $holeQuery->get()->firstRow(DATASET_TYPE_ARRAY);

            $userIDs = array_values(array_unique(explode(",", $holeResult["holeInsertUserIDs"])));
            $hole = new CommentThreadStructureHole(
                parentCommentID: $parentCommentID,
                offset: $holeOffset,
                depth: 1,
                userIDs: $userIDs,
                countAllComments: $holeResult["holeCount"],
                countAllUsers: count($userIDs)
            );
            $threadStructure->appendItem($hole);
        }

        $holeUserIDs = $threadStructure->getHolePreloadUserIDs();
        $userFragments = $this->userModel->getUserFragments($holeUserIDs);
        $threadStructure->applyHoleUserFragments($userFragments);

        $threadStructure->offsetDepth($depthOffset);

        return $threadStructure;
    }

    /**
     * Create a database query for recursively selecting comment fragments.
     *
     * @param array $where
     * @param array $options
     *
     * @return \Gdn_SQLDriver
     */
    private function createRecursiveCommentQuery(array $where, array $options): \Gdn_SQLDriver
    {
        $initialQuery = $this->db
            ->createSql()
            ->select("CommentID as commentID")
            ->select("parentCommentID")
            ->select(new Select("1", "depth"))
            ->from("Comment")
            ->where($where)
            ->applyModelOptions($options);

        return $this->db
            ->createSql()
            ->with("InitialComments", $initialQuery)
            ->withRecursive(
                "CommentTree",
                $this->db
                    ->createSql()
                    ->select("*")
                    ->from("@InitialComments"),
                $this->db
                    ->createSql()
                    ->select("c2.CommentID as commentID")
                    ->select("c2.parentCommentID")
                    ->select(new Select("ct.depth + 1", "depth"))
                    ->from("Comment c2")
                    ->join("@CommentTree ct", "c2.parentCommentID = ct.CommentID", "inner")
            )
            ->from("@CommentTree ct");
    }

    /**
     * Select a comment fragment for a thread.
     *
     * @param int $parentCommentID
     *
     * @return array{commentID: int, parentCommentID: int|null, depth: int, parentRecordType: string, parentRecordID: int}
     */
    public function selectThreadCommentFragment(int $parentCommentID): array
    {
        $query = $this->db
            ->createSql()
            ->withRecursive(
                "ParentCommentTree",
                $this->db
                    ->createSql()
                    ->select("CommentID", "", "commentID")
                    ->select("parentCommentID")
                    ->select(new Select("1", "depth"))
                    ->from("Comment")
                    ->where(["CommentID" => $parentCommentID]),

                $this->db
                    ->createSql()
                    ->select("c.CommentID", "", "commentID")
                    ->select("c.parentCommentID")
                    ->select(new Select("pct.depth + 1"))
                    ->from("Comment c")
                    ->join("@ParentCommentTree pct", "c.CommentID = pct.parentCommentID", "inner")
            )
            ->with(
                "CommentsWithDepth",
                $this->db
                    ->createSql()
                    // This is passing user input into a SQL query but our method signature ensures it's an integer.
                    ->select(new Select($parentCommentID, "commentID"))
                    ->select("pct.depth", "max", "depth")
                    ->from("@ParentCommentTree pct")
                    ->groupBy([new RawExpression("1")])
            )
            ->select("cwd.*")
            ->select("c.parentCommentID")
            ->select(new Select("COALESCE(c.parentRecordType, 'discussion')", "parentRecordType"))
            ->select(new Select("COALESCE(c.parentRecordID, c.DiscussionID)", "parentRecordID"))
            ->from("@CommentsWithDepth cwd")
            ->where(["cwd.CommentID" => $parentCommentID])
            ->join("Comment c", "c.CommentID = cwd.commentID", "inner");

        $row = $query->get()->firstRow(DATASET_TYPE_ARRAY);

        if (!$row) {
            throw new NotFoundException("ParentComment", [
                "commentID" => $parentCommentID,
            ]);
        }

        return $row;
    }
}
