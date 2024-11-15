<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use CommentModel;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Vanilla\Database\Select;
use Vanilla\Database\SetLiterals\Increment;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use Vanilla\Models\Model;
use Webmozart\Assert\Assert;

/**
 * Model for calculating comment threads.
 */
class CommentThreadModel
{
    public const OPT_THREAD_STRUCTURE = "threadStructureOptions";
    private null|QuoteEmbedFilter $quoteEmbedFilter = null;

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
            Model::OPT_LIMIT => null,
        ]);

        $depthOffset = 0;
        if ($parentCommentID !== null) {
            $parentComment = $this->selectThreadCommentFragment($parentCommentID);
            if (
                $parentComment["parentRecordType"] !== $where["parentRecordType"] ||
                $parentComment["parentRecordID"] !== $where["parentRecordID"]
            ) {
                throw new ClientException("Parent comment is not part of the correct thread.", 400, [
                    "parentCommentID" => $parentCommentID,
                    "actual" => [
                        "parentRecordType" => $where["parentRecordType"],
                        "parentRecordID" => $where["parentRecordID"],
                    ],
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

        $threadStructure = new CommentThreadStructure(
            $parentCommentID,
            $rows,
            $pagingCount,
            options: $options[self::OPT_THREAD_STRUCTURE] ?? new CommentThreadStructureOptions()
        );

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
     * Given a comment row, resolve the top level parent comment of it.
     *
     * @param int $commentID The initial comment row.
     * @return array The resolved comment row.
     *
     * @throws NotFoundException If the top level parent comment could not be found.
     */
    public function resolveTopLevelParentComment(int $commentID): array
    {
        $found = $this->db
            ->createSql()
            ->withRecursive(
                "ParentCommentTree",
                $this->db
                    ->createSql()
                    ->select("CommentID")
                    ->select("parentCommentID")
                    ->from("Comment")
                    ->where("CommentID", $commentID),
                $this->db
                    ->createSql()
                    ->select("c2.CommentID")
                    ->select("c2.parentCommentID")
                    ->from("Comment c2")
                    ->join("@ParentCommentTree c1", "c1.parentCommentID = c2.CommentID", "inner")
            )
            ->select("*")
            ->from("Comment c")
            ->whereInSubquery(
                "CommentID",
                $this->db
                    ->createSql()
                    ->select("CommentID")
                    ->from("@ParentCommentTree")
                    ->where("parentCommentID is null")
            )
            ->limit(1)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        if (!$found) {
            throw new NotFoundException("Could not resolve top level parent comment because the comment is orphaned.", [
                "commentID" => $commentID,
            ]);
        }
        return $found;
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
                    ->select(["CommentID", "parentCommentID", "depth"])
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

    /**
     * Handle aggregate counts if a comment is deleted.
     *
     * @param array $commentRow
     * @return void
     */
    public function handleParentCommentDeleteSideEffects(array $commentRow): void
    {
        $parentCommentID = $commentRow["parentCommentID"] ?? null;

        if ($parentCommentID !== null) {
            $totalChildrenChange = $commentRow["countChildComments"] + 1;
            $totalScoreChange = $commentRow["scoreChildComments"] + ($commentRow["Score"] ?? 0);

            $this->updateParentsRecursively(
                firstParentCommentID: $parentCommentID,
                set: [
                    "countChildComments" => new Increment(-$totalChildrenChange),
                    "scoreChildComments" => new Increment(-$totalScoreChange),
                ]
            );
        }
    }

    /**
     * Handle aggegate counts if a comment is inserted or updated.
     *
     * @param array $commentRow
     * @param array|null $prevCommentRow
     * @return void
     */
    public function handleParentCommentInsertSideEffects(array $commentRow, array|null $prevCommentRow): void
    {
        // We have to do aggregate counts for the parents.
        $parentCommentID = $commentRow["parentCommentID"] ?? null;
        $prevParentCommentID = $prevCommentRow["parentCommentID"] ?? null;

        if ($parentCommentID === null && $prevParentCommentID === null) {
            // there is no parent comment so nothing to do.
            return;
        }

        if ($prevCommentRow === null) {
            // This is a fresh insert. Let's just calculate the new counts.
            $this->updateParentsRecursively(
                firstParentCommentID: $parentCommentID,
                set: [
                    "countChildComments" => new Increment(1),
                    "scoreChildComments" => new Increment($commentRow["scoreChildComments"]),
                ]
            );
        } else {
            // We are moving a comment from one parent to another.
            $this->handleParentCommentDeleteSideEffects($prevCommentRow);

            // Now do the new parents.
            $totalChildrenChange = $commentRow["countChildComments"] + 1;
            $totalScoreChange = $commentRow["scoreChildComments"] + ($commentRow["Score"] ?? 0);
            $this->updateParentsRecursively(
                firstParentCommentID: $parentCommentID,
                set: [
                    "countChildComments" => new Increment($totalChildrenChange),
                    "scoreChildComments" => new Increment($totalScoreChange),
                ]
            );
        }
    }

    public function updateParentsRecursively(int $firstParentCommentID, array $set): void
    {
        $query = $this->db
            ->createSql()
            ->withRecursive(
                "ParentCommentTree",
                $this->db
                    ->createSql()
                    ->select("CommentID")
                    ->select("parentCommentID")
                    ->from("Comment")
                    ->where("CommentID", $firstParentCommentID),
                $this->db
                    ->createSql()
                    ->select("c2.CommentID")
                    ->select("c2.parentCommentID")
                    ->from("Comment c2")
                    ->join("@ParentCommentTree c1", "c1.parentCommentID = c2.CommentID", "inner")
            )
            ->from("Comment c")
            ->set($set)
            ->whereInSubquery(
                "CommentID",
                $this->db
                    ->createSql()
                    ->select("CommentID")
                    ->from("@ParentCommentTree")
            );
        $query->put();
    }

    /**
     * Process a comment row to render a parent comment as an HTML quote.
     *
     * @param array $row
     * @return string
     * @throws ValidationException
     */
    public function renderParentCommentAsQuote(array $row): string
    {
        if (!isset($row["parentCommentBody"])) {
            // The comment was likely deleted.
            return "";
        }

        $quoteData = [
            "bodyRaw" => $row["parentCommentBody"],
            "format" => $row["parentCommentFormat"],
            "insertUserID" => $row["parentCommentInsertUserID"],
            "dateInserted" => $row["parentCommentDateInserted"],
            "discussionID" => $row["parentDiscussionID"],
            "recordID" => $row["parentCommentID"],
            "recordType" => "comment",
            "url" => CommentModel::commentUrl([
                "CommentID" => $row["parentCommentID"],
                "CategoryID" => $row["categoryID"] ?? $row["CategoryID"],
            ]),
            "embedType" => QuoteEmbed::TYPE,
        ];
        $this->userModel->expandUsers($quoteData, ["insertUserID"]);
        $quote = new QuoteEmbed($quoteData);

        $filter = $this->getQuoteEmbedFilter();
        $quote = $filter->filterEmbed($quote);
        return $quote->renderHtml();
    }

    /**
     * Fetch the QuoteEmbedFilter as a singleton.
     *
     * @return QuoteEmbedFilter
     */
    private function getQuoteEmbedFilter(): QuoteEmbedFilter
    {
        if ($this->quoteEmbedFilter === null) {
            $this->quoteEmbedFilter = Gdn::getContainer()->get(QuoteEmbedFilter::class);
        }

        return $this->quoteEmbedFilter;
    }
}
