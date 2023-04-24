<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Search;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\PermissionException;
use Vanilla\Schema\RangeExpression;
use Vanilla\Search\MysqlSearchQuery;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Utility\ArrayUtils;
use UsersApiController;
use Vanilla\Utility\ModelUtils;

/**
 * Search record type for a user.
 */
class UserSearchType extends AbstractSearchType
{
    /** @var UsersApiController $usersApi */
    protected $usersApi;

    /** @var array $schemaFields Additional registered schema properties */
    private $schemaFields = [];

    /** @var array $filters Additional user filters to apply to the search driver */
    private $filters = [];

    /** @var \Gdn_Session */
    private $session;

    /** @var ProfileFieldModel */
    private ProfileFieldModel $profileFieldModel;

    /**
     * UserSearchType constructor.
     *
     * @param \UsersApiController $usersApi
     * @param \Gdn_Session $session
     * @param ProfileFieldModel $profileFieldModel
     */
    public function __construct(
        UsersApiController $usersApi,
        \Gdn_Session $session,
        ProfileFieldModel $profileFieldModel
    ) {
        $this->usersApi = $usersApi;
        $this->session = $session;
        $this->profileFieldModel = $profileFieldModel;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return "user";
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string
    {
        return "user";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "user";
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array
    {
        try {
            $results = $this->usersApi->index([
                "userID" => implode(",", $recordIDs),
                "expand" => [ModelUtils::EXPAND_CRAWL],
            ]);
            $results = $results->getData();

            $showFullSchema = $this->usersApi->checkPermission();
            $outSchema = $showFullSchema ? $this->usersApi->userSchema() : $this->usersApi->viewProfileSchema();

            $resultItems = array_map(function ($result) use ($outSchema) {
                $mapped = ArrayUtils::remapProperties($result, [
                    "recordID" => "userID",
                ]);
                $mapped["recordType"] = $this->getRecordType();
                $mapped["type"] = $this->getType();
                $mapped["userInfo"] = $result;

                $userResultItem = new UserSearchResultItem($outSchema, $mapped);

                return $userResultItem;
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

            $searchableFields = ["sortName"];
            if ($this->canSearchEmails()) {
                if ($email = $query->getQueryParameter("email")) {
                    $query->whereText($email, ["email"], SearchQuery::MATCH_WILDCARD);
                }
                $searchableFields[] = "email";
            }

            if ($allFieldText = $query->getQueryParameter("query")) {
                $query->whereText(strtolower($allFieldText), $searchableFields, SearchQuery::MATCH_WILDCARD);
            }

            if ($name = $query->getQueryParameter("name")) {
                $query->whereText(strtolower($name), ["sortName"], SearchQuery::MATCH_WILDCARD);
            }

            if ($roles = $query->getQueryParameter("roleIDs")) {
                $query->setFilter("roles.roleID", $roles);
            }

            $this->applyProfileFieldFilters($query);

            // Only users that are not banned are allowed.
            // Users are indexed with 0 for non-banned, >0 for various ban statuses.
            $query->setFilter("banned", [0]);

            foreach ($this->filters as $filterName => $filterField) {
                if ($filterValue = $query->getQueryParameter($filterName, false)) {
                    $query->setFilter($filterField, $filterValue);
                }
            }

            if ($dateInserted = $query->getQueryParameter("dateInserted")) {
                $query->setDateFilterSchema("dateInserted", $dateInserted);
            }

            if ($lastActiveDate = $query->getQueryParameter("dateLastActive")) {
                $query->setDateFilterSchema("dateLastActive", $lastActiveDate);
            }

            // Sorts
            $sort = $query->getQueryParameter("sort", "dateLastActive");
            $sortField = ltrim($sort, "-");
            $direction = $sortField === $sort ? SearchQuery::SORT_ASC : SearchQuery::SORT_DESC;
            if ($sortField === "name") {
                $sortField = "sortName.keyword";
            }

            $query->setSort($direction, $sortField);
        }
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
        $schemaFields = array_merge(
            [
                "email:s?" => [
                    "x-search-filter" => true,
                ],
                "roleIDs:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-search-filter" => true,
                ],
                "dateLastActive?" => new DateFilterSchema([
                    "x-search-filter" => true,
                ]),
                "sort:s?" => [
                    "enum" => ["dateLastActive", "-dateLastActive"],
                ],
                "profileFields:o?",
            ],
            $this->schemaFields
        );
        return Schema::parse($schemaFields);
    }

    /**
     * Returns a schema for validating profile field filters based on current profile fields.
     *
     * @return Schema
     */
    public function buildProfileFieldSchema(): Schema
    {
        $schemaArray = [];

        foreach ($this->getIndexedProfileFields() as $name => $field) {
            $formType = $field["formType"];
            $dataType = $field["dataType"];

            switch ([$dataType, $formType]) {
                case [ProfileFieldModel::DATA_TYPE_TEXT, ProfileFieldModel::FORM_TYPE_DROPDOWN]:
                case [ProfileFieldModel::DATA_TYPE_STRING_MUL, ProfileFieldModel::FORM_TYPE_DROPDOWN]:
                case [ProfileFieldModel::DATA_TYPE_STRING_MUL, ProfileFieldModel::FORM_TYPE_TOKENS]:
                    $schemaArray["$name:a?"] = [
                        "items" => ["type" => $this->profileFieldModel->getSchemaType($dataType)],
                        "style" => "form",
                        "example" => "option1,option2,option3",
                    ];
                    break;
                case [ProfileFieldModel::DATA_TYPE_NUMBER_MUL, ProfileFieldModel::FORM_TYPE_TOKENS]:
                case [ProfileFieldModel::DATA_TYPE_NUMBER, ProfileFieldModel::FORM_TYPE_NUMBER]:
                case [ProfileFieldModel::DATA_TYPE_NUMBER, ProfileFieldModel::FORM_TYPE_DROPDOWN]:
                    $schemaArray["$name?"] = RangeExpression::createSchema([":int"], true);
                    break;
                case [ProfileFieldModel::DATA_TYPE_DATE, ProfileFieldModel::FORM_TYPE_DATE]:
                    $schemaArray["$name?"] = new DateFilterSchema([
                        "x-search-filter" => true,
                    ]);
                    break;
                default:
                    $schemaArray["$name?"] = ["type" => $this->profileFieldModel->getSchemaType($dataType)];
            }
        }

        return Schema::parse($schemaArray);
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchemaExtension(): Schema
    {
        return Schema::parse([
            "sort:s?" => [
                "enum" => ["countPosts", "-countPosts", "name", "-name"],
            ],
        ]);
    }

    /**
     * Generates prepares sql query string
     *
     * @param MysqlSearchQuery $query
     * @return string
     */
    public function generateSql(MysqlSearchQuery $query): string
    {
        // mysql is not implemented
        return "";
    }

    /**
     * @inheritdoc
     * @throws NotFoundException|ForbiddenException|ClientException|ValidationException
     */
    public function validateQuery(SearchQuery $query): void
    {
        $hasSiteScope = $query->getQueryParameter("scope", "site") === "site";
        $types = $query->getQueryParameter("types", []);
        $recordTypes = $query->getQueryParameter("recordTypes", []);
        if (!$hasSiteScope && (in_array($this->getType(), $types) || in_array($this->getRecordType(), $recordTypes))) {
            throw new ClientException("Cannot search users with any scope other than `site`.", 422);
        }

        if (!$this->canSearchEmails() && $query->getQueryParameter("email", null) !== null) {
            throw new PermissionException("You don't have permission to search by email.");
        }

        $this->validateProfileFieldFilters($query);
    }

    /**
     * @return bool
     */
    private function canSearchEmails(): bool
    {
        return $this->usersApi->checkPermission();
    }

    /**
     * Add fields to schema
     *
     * @param array $schemaFields
     */
    public function addSchemaFields(array $schemaFields)
    {
        $this->schemaFields = array_merge($this->schemaFields, $schemaFields);
    }

    /**
     * Add filters to apply
     *
     * @param string $filterName
     * @param string $filterField
     */
    public function addFilter(string $filterName, string $filterField)
    {
        $this->filters[$filterName] = $filterField;
    }

    /**
     * User's should not be searched with other types. Their
     * @inheritdoc
     */
    public function isExclusiveType(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function userHasPermission(): bool
    {
        try {
            $this->usersApi->checkPermission();
            return true;
        } catch (PermissionException $permissionException) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getSingularLabel(): string
    {
        return \Gdn::translate("User");
    }

    /**
     * @return string
     */
    public function getPluralLabel(): string
    {
        return \Gdn::translate("Users");
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int
    {
        return null;
    }

    /**
     * Validates profile field filters in the SearchQuery object.
     *
     * @param SearchQuery $query
     * @return void
     * @throws NotFoundException|ForbiddenException|ValidationException
     */
    private function validateProfileFieldFilters(SearchQuery $query)
    {
        $profileFields = $query->getQueryParameter("profileFields", []);
        $databaseProfileFields = $this->getIndexedProfileFields();

        foreach (array_keys($profileFields) as $apiName) {
            if (!isset($databaseProfileFields[$apiName])) {
                throw new NotFoundException("No profile field found with name $apiName");
            }
            if (!$this->profileFieldModel->canView(null, $databaseProfileFields[$apiName])) {
                throw new ForbiddenException("You are not allowed to filter by profile field with name $apiName");
            }
        }
        $this->buildProfileFieldSchema()->validate($profileFields);
    }

    /**
     * Updates the SearchQuery object to add filters for profile fields.
     *
     * @param SearchQuery $query
     * @return void
     */
    private function applyProfileFieldFilters(SearchQuery $query)
    {
        if (!($profileFields = $query->getQueryParameter("profileFields"))) {
            return;
        }
        try {
            // Validate again to get profile fields with the correct data types.
            $profileFields = $this->buildProfileFieldSchema()->validate($profileFields);
        } catch (\Throwable $e) {
            // This shouldn't happen because we did a validation pass already.
            return;
        }

        $databaseProfileFields = $this->getIndexedProfileFields();

        foreach ($profileFields as $apiName => $value) {
            $databaseProfileField = $databaseProfileFields[$apiName] ?? null;
            $formType = $databaseProfileField["formType"] ?? null;
            $dataType = $databaseProfileField["dataType"] ?? null;
            switch ([$dataType, $formType]) {
                case [ProfileFieldModel::DATA_TYPE_TEXT, ProfileFieldModel::FORM_TYPE_TEXT]:
                case [ProfileFieldModel::DATA_TYPE_TEXT, ProfileFieldModel::FORM_TYPE_TEXT_MULTILINE]:
                    if (strpos($value, "*") !== false) {
                        $query->whereText($value, ["profileFields.$apiName.keyword"], SearchQuery::MATCH_WILDCARD);
                    } else {
                        $query->whereText($value, ["profileFields.$apiName"]);
                    }
                    break;
                case [ProfileFieldModel::DATA_TYPE_BOOL, ProfileFieldModel::FORM_TYPE_CHECKBOX]:
                    $query->setFilter("profileFields.$apiName", [$value]);
                    break;
                case [ProfileFieldModel::DATA_TYPE_NUMBER, ProfileFieldModel::FORM_TYPE_NUMBER]:
                case [ProfileFieldModel::DATA_TYPE_NUMBER_MUL, ProfileFieldModel::FORM_TYPE_TOKENS]:
                    /** @var $value RangeExpression */
                    foreach ($value->getValues() as $op => $val) {
                        switch ($op) {
                            case "=":
                                $query->setFilter("profileFields.$apiName", is_array($val) ? $val : [$val]);
                                break;
                            case "<":
                                $query->setFilterRange("profileFields.$apiName", null, $val, true, false);
                                break;
                            case ">":
                                $query->setFilterRange("profileFields.$apiName", $val, null, true, false);
                                break;
                            case "<=":
                                $query->setFilterRange("profileFields.$apiName", null, $val, false, false);
                                break;
                            case ">=":
                                $query->setFilterRange("profileFields.$apiName", $val, null, false, false);
                                break;
                        }
                    }
                    break;
                case [ProfileFieldModel::DATA_TYPE_TEXT, ProfileFieldModel::FORM_TYPE_DROPDOWN]:
                case [ProfileFieldModel::DATA_TYPE_STRING_MUL, ProfileFieldModel::FORM_TYPE_TOKENS]:
                    $databaseOptions = $databaseProfileField["dropdownOptions"] ?? [];
                    $filteredOptions = array_intersect($databaseOptions, $value);
                    $query->setFilter("profileFields.$apiName.keyword", $filteredOptions);
                    break;
                case [ProfileFieldModel::DATA_TYPE_DATE, ProfileFieldModel::FORM_TYPE_DATE]:
                    $query->setDateFilterSchema("profileFields.$apiName", $value);
                    break;
            }
        }
    }

    /**
     * Helper method to get enabled profile fields indexed by apiName
     *
     * @return array
     */
    private function getIndexedProfileFields(): array
    {
        $profileFields = $this->profileFieldModel->getProfileFields(["enabled" => true]);
        return array_column($profileFields, null, "apiName");
    }
}
