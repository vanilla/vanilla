<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\HttpException;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Models\PostFieldModel;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Schema\RangeExpression;
use Vanilla\Search\BoostableSearchQueryInterface;
use Vanilla\Search\MysqlSearchQuery;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Search\SearchTypeQueryExtenderInterface;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Search record type for a discussion.
 */
class DiscussionSearchType extends AbstractSearchType
{
    /** @var array extenders */
    protected $extenders = [];

    protected $extendersEnabled = true;

    /**
     * DI.
     *
     * @param \DiscussionsApiController $discussionsApi
     * @param \CategoryModel $categoryModel
     * @param \UserModel $userModel
     * @param \TagModel $tagModel
     * @param BreadcrumbModel $breadcrumbModel
     * @param ConfigurationInterface $config
     */
    public function __construct(
        protected \DiscussionsApiController $discussionsApi,
        protected \CategoryModel $categoryModel,
        protected \UserModel $userModel,
        protected \TagModel $tagModel,
        protected BreadcrumbModel $breadcrumbModel,
        private ConfigurationInterface $config,
        protected PostFieldModel $postFieldModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return "discussion";
    }

    /**
     * Register search query extender
     *
     * @param SearchTypeQueryExtenderInterface $extender
     */
    public function registerQueryExtender(SearchTypeQueryExtenderInterface $extender)
    {
        $this->extenders[] = $extender;
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string
    {
        return "discussion";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "discussion";
    }

    /**
     * @return bool
     */
    public function supportsCollapsing(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canBeOptimizedIntoRecordType(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array
    {
        if ($this->allowsExtenders($query)) {
            foreach ($this->extenders as $extender) {
                $extender->extendPermissions();
            }
        }
        try {
            $results = $this->discussionsApi->index([
                "discussionID" => implode(",", $recordIDs),
                "limit" => 100,
                "expand" => [ModelUtils::EXPAND_CRAWL, "tagIDs", "tags"],
            ]);
            $results = $results->getData();

            if (!$results) {
                return [];
            }

            $resultItems = array_map(function ($result) {
                $mapped = ArrayUtils::remapProperties($result, [
                    "recordID" => "discussionID",
                ]);
                $mapped["recordType"] = $this->getRecordType();
                $mapped["type"] = $this->getType();
                $mapped["legacyType"] = $this->getSingularLabel();
                $mapped["breadcrumbs"] = $this->breadcrumbModel->getForRecord(
                    new ForumCategoryRecordType($mapped["categoryID"])
                );
                return new DiscussionSearchResultItem($mapped);
            }, $results);
            return $resultItems;
        } catch (HttpException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function applyToQuery(SearchQuery $query)
    {
        if ($query instanceof MysqlSearchQuery) {
            $query->addSql($this->generateSql($query));
        } else {
            $query->addIndex($this->getIndex());

            if ($discussionID = $query->getQueryParameter("discussionID", false)) {
                $query->setFilter("DiscussionID", [$discussionID]);
            }
            $categoryIDs = $this->getCategoryIDs($query);
            if (!empty($categoryIDs)) {
                $query->setFilter("CategoryID", $categoryIDs);
            }

            if ($this->allowsExtenders($query)) {
                /** @var SearchTypeQueryExtenderInterface $extender */
                foreach ($this->extenders as $extender) {
                    $extender->extendQuery($query);
                }
            }

            if ($query instanceof BoostableSearchQueryInterface && $query->getBoostParameter("discussionRecency")) {
                $query->startBoostQuery();
                $query->boostFieldRecency("dateInserted");
                $query->endBoostQuery();
            }

            // tags
            // Notably includes 0 to still allow other normalized records if set.
            $tagNames = $query->getQueryParameter("tags", []);
            $tagIDs = array_values($this->tagModel->getTagIDsByName($tagNames));
            $tagOp = $query->getQueryParameter("tagOperator", "or");
            if (!empty($tagIDs)) {
                $query->setFilter("tagIDs", $tagIDs, false, $tagOp);
            }

            $includedInsertUserRoleIDs = $query->getQueryParameter("includedInsertUserRoleIDs");
            if (!empty($includedInsertUserRoleIDs)) {
                $query->setFilter("insertUserRoleIDs", $includedInsertUserRoleIDs);
            }

            $excludedInsertUserRoleIDs = $query->getQueryParameter("excludedInsertUserRoleIDs");
            if (!empty($excludedInsertUserRoleIDs)) {
                $query->setFilter("insertUserRoleIDs", $excludedInsertUserRoleIDs, false, SearchQuery::FILTER_OP_NOT);
            }

            $excludedInsertUserIDs = $query->getQueryParameter("excludedInsertUserIDs");
            if (!empty($excludedInsertUserIDs)) {
                $query->setFilter("insertUserID", $excludedInsertUserIDs, filterOp: SearchQuery::FILTER_OP_NOT);
            }

            $statusIDs = $query->getQueryParameter("statusID");
            if (!empty($statusIDs)) {
                $statusIDs[] = null;
                $query->setFilter("statusID", $statusIDs, filterOp: SearchQuery::FILTER_OP_OR);
            }
            $answerStatusID = $query->getQueryParameter("answerStatusID");
            if (!empty($answerStatusID)) {
                $answerStatusID[] = null;
                $query->setFilter("answerStatusID", $answerStatusID, filterOp: SearchQuery::FILTER_OP_OR);
            }

            $postTypeIDs = $query->getQueryParameter("postTypeID");
            if (!empty($postTypeIDs)) {
                $query->setFilter("postTypeID", $postTypeIDs, filterOp: SearchQuery::FILTER_OP_OR);
            }

            $this->applyPostMetaFilters($query);
        }
    }

    /**
     * @return float|null
     */
    public function getBoostValue(): ?float
    {
        return $this->config->get("Elastic.Boost.Discussion", 0.5);
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema
    {
        return Schema::parse([
            "discussionID:i?",
            "categoryID:i?",
            "categoryIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "followedCategories:b?",
            "includeChildCategories:b?",
            "includeArchivedCategories:b?",
            "tags:a?" => [
                "items" => [
                    "type" => "string",
                ],
            ],
            "tagOperator:s?" => [
                "items" => [
                    "type" => "string",
                    "enum" => [SearchQuery::FILTER_OP_OR, SearchQuery::FILTER_OP_AND],
                ],
            ],
            "includedInsertUserRoleIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "excludedInsertUserRoleIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "excludedInsertUserIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "postMeta:o?" => $this->getPostMetaFilterSchema(),
            "statusID:a?" => [
                "items" => [
                    "type" => "integer",
                ],
                "style" => "form",
            ],
            "answerStatusID:a?" => [
                "items" => [
                    "type" => "string",
                    "enum" => ["accepted", "rejected", "pending"],
                ],
                "style" => "form",
            ],
            "postTypeID:a?" => [
                "items" => [
                    "type" => "string",
                ],
                "style" => "form",
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchemaExtension(): Schema
    {
        return Schema::parse([
            "sort:s?" => [
                "enum" => ["score", "-score", "hot", "-hot"],
            ],
        ]);
    }

    /**
     * Get article boost types.
     *
     * @return Schema|null
     */
    public function getBoostSchema(): ?Schema
    {
        return Schema::parse([
            "discussionRecency:b" => [
                "default" => true,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void
    {
        // Validate category IDs.
        $categoryID = $query->getQueryParameter("categoryID", null);
        if ($categoryID !== null && !$this->categoryModel::checkPermission($categoryID, "Vanilla.Discussions.View")) {
            throw new PermissionException("Vanilla.Discussions.View");
        }
        $categoryIDs = $query->getQueryParameter("categoryIDs", null);
        if ($categoryID !== null && $categoryIDs !== null) {
            $validation = new Validation();
            $validation->addError("categoryID", "Only one of categoryID, categoryIDs are allowed.");
            throw new ValidationException($validation);
        }
        $this->validatePostMetaFilters($query);
    }

    /**
     * Generates prepares sql query string
     *
     * @param MysqlSearchQuery $query
     * @return string
     */
    public function generateSql(MysqlSearchQuery $query): string
    {
        /** @var \Gdn_SQLDriver $db */
        $db = clone $query->getDB();

        $categoryIDs = $this->getCategoryIDs($query);

        if ($categoryIDs === []) {
            return "";
        }

        // Build base query
        $db->from("Discussion d")
            ->select("d.DiscussionID as recordID, d.Name as Title, d.Format, d.CategoryID, d.Score")
            ->select("d.DiscussionID", "concat('/discussion/', %s)", "Url")
            ->select("d.DateInserted")
            ->select("d.Type as recordType")
            ->select("d.InsertUserID as UserID")
            ->select("'discussion'", "", "type")
            ->orderBy("d.DateInserted", "desc");
        if (false !== $query->get("expandBody", null)) {
            $db->select("d.Body as body");
        }

        $terms = $query->get("query", false);
        if ($terms) {
            $terms = $db->quote("%" . str_replace(["%", "_"], ["\%", "\_"], $terms) . "%");
            $db->beginWhereGroup();
            foreach (["d.Name", "d.Body"] as $field) {
                $db->orWhere("$field like", $terms, true, false);
            }
            $db->endWhereGroup();
        }

        if ($name = $query->get("name", false)) {
            $db->where(
                "d.Name like",
                $db->quote("%" . str_replace(["%", "_"], ["\%", "\_"], $name) . "%"),
                true,
                false
            );
        }

        $this->applyUserIDs($db, $query, "d");
        $this->applyDateInsertedSql($db, $query, "d");

        $discussionID = $query->get("discussionID", false);
        if ($discussionID !== false) {
            $db->where("d.DiscussionID", $discussionID);
        }

        if (!empty($categoryIDs)) {
            $db->whereIn("d.CategoryID", $categoryIDs);
        }

        $limit = $query->get("limit", 100);
        $offset = $query->get("offset", 0);
        $db->limit($limit + $offset);

        $sql = $db->getSelect(true);
        $db->reset();

        return $sql;
    }

    /**
     * Apply the dateInserted parameters.
     *
     * @param \Gdn_SQLDriver $sql
     * @param MysqlSearchQuery $query
     * @param string $tableAlias
     */
    protected function applyDateInsertedSql(\Gdn_SQLDriver $sql, MysqlSearchQuery $query, string $tableAlias)
    {
        $dateInserted = $query->getQueryParameter("dateInserted");

        if ($dateInserted) {
            $schema = new DateFilterSchema();
            $sql->where(
                DateFilterSchema::dateFilterField("$tableAlias.DateInserted", $schema->validate($dateInserted))
            );
        }
    }

    /**
     * Apply the insertUsers part of the SQL query.
     *
     * @param \Gdn_SQLDriver $sql
     * @param MysqlSearchQuery $query
     * @param string $tableAlias
     */
    protected function applyUserIDs(\Gdn_SQLDriver $sql, MysqlSearchQuery $query, string $tableAlias)
    {
        $insertUserIDs = $query->getQueryParameter("insertUserIDs", false);
        $insertUserNames = $query->getQueryParameter("insertUserNames", false);
        if (!$insertUserIDs && $insertUserNames) {
            $users = $this->userModel
                ->getWhere([
                    "name" => $insertUserNames,
                ])
                ->resultArray();
            $insertUserIDs = array_column($users, "UserID");
        }

        if ($insertUserIDs) {
            $sql->where("$tableAlias.InsertUserID", $insertUserIDs);
        }
    }

    /**
     * Get category ids from DB if query has it as a filter
     *
     * @param SearchQuery $query
     * @return array|null
     */
    protected function getCategoryIDs(SearchQuery $query): ?array
    {
        $categoryIDs = $this->categoryModel->getSearchCategoryIDs(
            $query->getQueryParameter("categoryID"),
            $query->getQueryParameter("followedCategories"),
            $query->getQueryParameter("includeChildCategories"),
            $query->getQueryParameter("includeArchivedCategories"),
            $query->getQueryParameter("categoryIDs"),
            "Discussion"
        );
        if ($this->allowsExtenders($query)) {
            /** @var SearchTypeQueryExtenderInterface $extender */
            foreach ($this->extenders as $extender) {
                $categoryIDs = $extender->extendCategories($categoryIDs);
            }
        }
        return $categoryIDs;
    }

    /**
     * Get user ids by their name if query has insertUserNames argument
     *
     * @param array $userNames
     * @return array|null
     */
    protected function getUserIDs(array $userNames): ?array
    {
        if (!empty($userNames)) {
            $users = $this->userModel
                ->getWhere([
                    "name" => $userNames,
                ])
                ->resultArray();
            $userIDs = array_column($users, "UserID");
            return $userIDs;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getSingularLabel(): string
    {
        return \Gdn::translate("Discussion");
    }

    /**
     * @return string
     */
    public function getPluralLabel(): string
    {
        return \Gdn::translate("Discussions");
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array
    {
        return [0];
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int
    {
        return ($guid - 1) / 10;
    }

    /**
     * Enable or disable extenders for this search type.
     *
     * @param bool $state
     * @return void
     */
    public function toggleExtenders(bool $state): void
    {
        $this->extendersEnabled = $state;
    }

    /**
     * If the query supports extenders and extenders are enabled for this search type.
     *
     * @param SearchQuery $query
     * @return bool
     */
    protected function allowsExtenders(SearchQuery $query): bool
    {
        return $query->supportsExtenders() && $this->extendersEnabled;
    }

    /**
     * Return a schema for validating post meta filters.
     *
     * @return Schema
     */
    public static function getPostMetaFilterSchema(): Schema
    {
        $schemaArray = [];
        $postFieldModel = \Gdn::getContainer()->get(PostFieldModel::class);
        $postFields = array_column($postFieldModel->getWhere(["isActive" => true]), null, "postFieldID");

        foreach ($postFields as $field) {
            $formType = $field["formType"];
            $dataType = $field["dataType"];
            $postFieldID = $field["postFieldID"];

            switch ([$dataType, $formType]) {
                case ["text", "text"]:
                case ["text", "text-multiline"]:
                    $schemaArray["$postFieldID?"] = ["type" => "string"];
                    break;
                case ["boolean", "checkbox"]:
                    $schemaArray["$postFieldID?"] = ["type" => "boolean", "example" => "true|false"];
                    break;
                case ["text", "dropdown"]:
                case ["string[]", "tokens"]:
                    $schemaArray["$postFieldID:a?"] = [
                        "items" => ["type" => "string"],
                        "style" => "form",
                        "example" => "option1,option2,option3",
                    ];
                    break;
                case ["number[]", "tokens"]:
                case ["number", "number"]:
                case ["number", "dropdown"]:
                    $schemaArray["$postFieldID?"] = RangeExpression::createSchema([":int"], true);
                    break;

                case ["date", "date"]:
                    $schemaArray["$postFieldID?"] = new DateFilterSchema();
                    break;
            }
        }

        return Schema::parse($schemaArray);
    }

    /**
     * Apply post meta filters to the query if we have them.
     *
     * @param SearchQuery $query
     * @return void
     * @throws \Exception
     */
    protected function applyPostMetaFilters(SearchQuery $query): void
    {
        $postMeta = $query->getQueryParameter("postMeta");
        if (empty($postMeta)) {
            return;
        }

        $postFields = array_column($this->postFieldModel->getWhere(["isActive" => true]), null, "postFieldID");
        foreach ($postMeta as $postFieldID => $value) {
            $formType = $postFields[$postFieldID]["formType"] ?? null;
            $dataType = $postFields[$postFieldID]["dataType"] ?? null;
            switch ([$dataType, $formType]) {
                case ["text", "text"]:
                case ["text", "text-multiline"]:
                    $query->whereText($value, ["postMeta.$postFieldID"], SearchQuery::MATCH_WILDCARD);
                    break;
                case ["boolean", "checkbox"]:
                    if ($value) {
                        $query->setFilter("postMeta.$postFieldID", [true]);
                    } else {
                        $query->setFilter("postMeta.$postFieldID", [true], filterOp: SearchQuery::FILTER_OP_NOT);
                    }
                    break;
                case ["text", "dropdown"]:
                case ["string[]", "tokens"]:
                    $databaseOptions = $postFields[$postFieldID]["dropdownOptions"] ?? [];
                    $filteredOptions = array_intersect($databaseOptions, $value);
                    $query->setFilter("postMeta.$postFieldID.keyword", $filteredOptions);
                    break;
                case ["number[]", "tokens"]:
                case ["number", "number"]:
                case ["number", "dropdown"]:
                    /** @var $value RangeExpression */
                    $query->setRangeExpressionFilter("postMeta.$postFieldID", $value);
                    break;
                case ["date", "date"]:
                    $query->setDateFilterSchema("postMeta.$postFieldID", $value);
                    break;
            }
        }
    }

    /**
     * Validate that the current user has permission to filter by post fields.
     *
     * @param SearchQuery $query
     * @return void
     * @throws ForbiddenException
     */
    private function validatePostMetaFilters(SearchQuery $query): void
    {
        $postMeta = $query->getQueryParameter("postMeta");
        if (!empty($postMeta)) {
            $availableFields = PostFieldModel::getAvailableViewFieldsForCurrentSessionUser();
            $postFieldIDs = array_column($availableFields, "postFieldID");
            $invalidFields = array_diff(array_keys($postMeta), $postFieldIDs);
            if (!empty($invalidFields)) {
                throw new ForbiddenException(
                    "You don't have permission to access the following fields: " . implode(", ", $invalidFields)
                );
            }
        }
    }
}
