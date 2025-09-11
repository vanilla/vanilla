<?php

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\ApiUtils;
use Garden\Web\Pagination;

/**
 * API controller for the /posts endpoint.
 */
class PostsApiController extends AbstractApiController
{
    public function __construct(
        private DiscussionModel $discussionModel,
        private CommentModel $commentModel,
        private CategoryModel $categoryModel,
        private Gdn_Database $database,
        private \Vanilla\Http\InternalClient $internalClient
    ) {
    }

    /**
     * Get posts by roles.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query): Data
    {
        $this->permission("session.valid");

        $in = Schema::parse([
            "includeComments:b?" => ["default" => true],
            "roleIDs" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
            "limit:i?" => ["default" => 50, "min" => 1, "max" => 100],
            "page:i?" => ["default" => 1, "min" => 1, "max" => 50],
            "sort" => [
                "default" => "score",
                "enum" => [
                    "score",
                    "-score",
                    "dateInserted",
                    "-dateInserted",
                    "dateLastComment",
                    "-dateLastComment",
                    "commentDate",
                    "-commentDate",
                ],
            ],
        ]);

        $query = $in->validate($query);

        $query["offset"] = ($query["page"] - 1) * $query["limit"];

        $rolesWhere = $this->getRolesWhere($query["roleIDs"]);

        // Only get categories that are visible to the user.
        $visibleCategories = $this->categoryModel->getVisibleCategories();
        if ($visibleCategories === true) {
            $visibleCategories = CategoryModel::categories();
        }
        $visibleCategoryIDs = array_column($visibleCategories, "CategoryID");

        $whereInCategories = "Where d.CategoryID IN (" . implode(", ", $visibleCategoryIDs) . ")";

        [$orderField, $orderDirection] = \Vanilla\Models\LegacyModelUtils::orderFieldDirection(
            $query["sort"] ?? "-DateLastComment"
        );

        $query["sort"] = ucfirst($orderField);

        if ($query["sort"] === "CommentDate") {
            $commentOrderBy = "c.DateInserted";
            $discussionOrderBy = "d.DateInserted";
            $query["sort"] = "DateInserted";
        } else {
            $commentOrderBy = $query["sort"] === "DateLastComment" ? "c.DateInserted" : "c." . $query["sort"];
            $discussionOrderBy = "d." . $query["sort"];
        }

        $sqlQuery =
            "
            (
    SELECT distinct
		'discussion' AS recordType,
		DiscussionID as recordID,
		d.DateInserted,
		d.DateLastComment,
		d.Score
	FROM
		GDN_Discussion d
		JOIN GDN_UserRole ur on ur.UserID = d.InsertUserID
		" .
            $whereInCategories .
            "
		AND " .
            $rolesWhere .
            "
	ORDER BY " .
            $discussionOrderBy .
            " " .
            $orderDirection .
            "
)
            ";

        if ($query["includeComments"]) {
            $sqlQuery .=
                "
            UNION
        (
            SELECT
		'comment' AS recordType,
		CommentID as recordID,
		c.DateInserted,
		c.DateInserted as DateLastComment,
		c.Score
	FROM
		GDN_Comment c
		JOIN GDN_Discussion d ON c.parentRecordType = 'discussion'
    AND c.parentRecordID = d.DiscussionID
        JOIN GDN_UserRole ur on ur.UserID = c.InsertUserID
        " .
                $whereInCategories .
                "
    AND " .
                $rolesWhere .
                "
	ORDER BY " .
                $commentOrderBy .
                " " .
                $orderDirection .
                "
)
            ";
        }

        $sqlQuery .=
            "
ORDER BY
	" .
            $query["sort"] .
            " " .
            $orderDirection .
            "
LIMIT
	" .
            $query["limit"] .
            "
OFFSET " .
            $query["offset"] .
            ";";

        $sql = $this->database->createSql();
        $records = $sql->query($sqlQuery)->resultArray();

        // Get the record IDs to fetch the discussions and comments.
        $discussionIDs = [];
        $commentIDs = [];
        foreach ($records as $record) {
            if ($record["recordType"] === "discussion") {
                $discussionIDs[] = $record["recordID"];
            } else {
                $commentIDs[] = $record["recordID"];
            }
        }

        $discussions = [];
        $comments = [];

        if (count($discussionIDs) > 0) {
            // Get normalized and validated discussions.
            $discussions = $this->internalClient
                ->get("/api/v2/discussions", [
                    "discussionID" => $discussionIDs,
                    "expand" => ["tags", "category", "excerpt", "breadcrumbs", "reactions"],
                    "limit" => count($discussionIDs),
                ])
                ->getBody();
            $discussions = array_column($discussions, null, "discussionID");
        }

        if (count($commentIDs) > 0) {
            // Get normalized and validated comments.
            $comments = $this->internalClient
                ->get("/api/v2/comments", [
                    "commentID" => $commentIDs,
                    "expand" => ["all"],
                    "limit" => count($commentIDs),
                ])
                ->getBody();
            $comments = array_column($comments, null, "commentID");
        }
        // Preserve the order of the records.
        $result = [];
        foreach ($records as $record) {
            if ($record["recordType"] === "discussion") {
                $discussion = $discussions[$record["recordID"]];
                if ($discussion) {
                    $post = array_merge(["recordType" => "discussion"], $discussion);

                    $post["uniqueID"] = $post["recordType"] . "-" . $post["discussionID"];
                }
            } else {
                $comment = $comments[$record["recordID"]];
                if ($comment) {
                    $post = array_merge(
                        [
                            "recordType" => "comment",
                            "excerpt" => \Gdn::formatService()->renderExcerpt($comment["body"], "html"),
                        ],
                        $comment
                    );
                    $post["uniqueID"] = $post["recordType"] . "-" . $post["commentID"];
                }
            }
            $result[] = $post;
        }

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/posts", $query, $in);

        return new Data($result, Pagination::tryCursorPagination($paging, $query, $result, "uniqueID"));
    }

    /**
     * Get the proper query string from the rolesQuery.
     *
     * @param \Vanilla\Schema\RangeExpression $rolesQuery
     * @return string
     */
    private function getRolesWhere(\Vanilla\Schema\RangeExpression $rolesQuery): string
    {
        $roleValues = $rolesQuery->getValues();
        if (isset($roleValues["="])) {
            $roleIDs = is_array($roleValues["="]) ? $roleValues["="] : [$roleValues["="]];
            $rolesWhere = "ur.RoleID IN (" . implode(", ", $roleIDs) . ")";
        } elseif (isset($roleValues[">="]) and isset($roleValues["<="])) {
            $rolesWhere = "ur.RoleID BETWEEN " . $roleValues[">="] . " AND " . $roleValues["<="];
        } elseif (isset($roleValues[">"]) || isset($roleValues["<"])) {
            $rolesWhere =
                "ur.RoleID " .
                (isset($roleValues[">"]) ? ">" : "<") .
                " " .
                (isset($roleValues[">"]) ? $roleValues[">"] : $roleValues["<"]);
        } else {
            throw new \InvalidArgumentException("Invalid roleIDs value");
        }
        return $rolesWhere;
    }
}
