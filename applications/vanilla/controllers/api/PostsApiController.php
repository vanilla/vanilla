<?php

use Garden\Schema\Schema;
use Garden\Web\Data;

/**
 * API controller for the /posts endpoint.
 */
class PostsApiController extends AbstractApiController
{
    public function __construct(
        private DiscussionModel $discussionModel,
        private CommentModel $commentModel,
        private CategoryModel $categoryModel,
        private Gdn_Database $database
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
            "roleIDs" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
            "limit:i?" => ["default" => 50, "max" => 100],
            "offset:i?" => ["default" => 0, "max" => 50],
            "sort" => [
                "default" => "score",
                "enum" => ["score", "-score", "dateInserted", "-dateInserted", "dateLastComment", "-dateLastComment"],
            ],
        ]);

        $query = $in->validate($query);

        $rolesWhere = $this->getRolesWhere($query["roleIDs"]);

        // Only get categories that are visible to the user.
        $visibleCategories = $this->categoryModel->getVisibleCategories();
        if ($visibleCategories === true) {
            $visibleCategories = CategoryModel::categories();
        }
        $visibleCategoryIDs = array_column($visibleCategories, "CategoryID");

        $whereInCategories = "Where d.CategoryID IN (" . implode(", ", $visibleCategoryIDs) . ")";

        if (str_starts_with($query["sort"], "-")) {
            $query["sort"] = substr($query["sort"], 1);
            $direction = "ASC";
        } else {
            $direction = "DESC";
        }

        $query["sort"] = ucfirst($query["sort"]);

        $commentOrderBy = $query["sort"] === "DateLastComment" ? "c.DateInserted" : "c." . $query["sort"];
        $discussionOrderBy = "d." . $query["sort"];

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
            $direction .
            "
	LIMIT " .
            $query["limit"] .
            "
	OFFSET " .
            $query["offset"] .
            "
)
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
            $direction .
            "
	LIMIT " .
            $query["limit"] .
            "
	OFFSET " .
            $query["offset"] .
            "
)
ORDER BY
	" .
            $query["sort"] .
            " " .
            $direction .
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

        // Get normalized and validated discussions.
        $discussions = array_column(
            $this->discussionModel->getWhere(["DiscussionID" => $discussionIDs])->resultArray(),
            null,
            "DiscussionID"
        );
        $discussionSchema = $this->discussionModel->schema();
        $discussionSchema = $discussionSchema->merge(Schema::parse(["recordType:s"]));

        // Get normalized and validated comments.
        $comments = array_column(
            $this->commentModel->getWhere(["CommentID" => $commentIDs])->resultArray(),
            null,
            "CommentID"
        );
        $commentSchema = $this->commentModel->schema();
        $commentSchema = $commentSchema->merge(Schema::parse(["recordType:s"]));

        // Preserve the order of the records.
        $result = [];
        foreach ($records as $record) {
            if ($record["recordType"] === "discussion") {
                $discussion = array_merge(
                    ["recordType" => "discussion"],
                    $this->discussionModel->normalizeRow($discussions[$record["recordID"]])
                );
                $discussion = $discussionSchema->validate($discussion);
                $result[] = $discussion;
            } else {
                $comment = array_merge(
                    ["recordType" => "comment"],
                    $this->commentModel->normalizeRow($comments[$record["recordID"]])
                );
                $comment = $commentSchema->validate($comment);
                $result[] = $comment;
            }
        }

        return new Data($result);
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
