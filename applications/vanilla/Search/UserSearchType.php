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
use Vanilla\ApiUtils;
use Vanilla\Cloud\ElasticSearch\Driver\Query\ElasticBool;
use Vanilla\Cloud\ElasticSearch\Driver\Query\ElasticTermsAssertion;
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
    const TYPE = "user";

    /** @var UsersApiController $usersApi */
    protected $usersApi;

    /** @var array $schemaFields Additional registered schema properties */
    private $schemaFields = [];

    /** @var array $filters Additional user filters to apply to the search driver */
    private $filters = [];

    /** @var \Gdn_Session */
    private $session;

    private \UserModel $userModel;

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
        \UserModel $userModel,
        ProfileFieldModel $profileFieldModel
    ) {
        $this->usersApi = $usersApi;
        $this->session = $session;
        $this->userModel = $userModel;
        $this->profileFieldModel = $profileFieldModel;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return self::TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string
    {
        return self::TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return self::TYPE;
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

            $showFullSchema = $this->usersApi->checkUserPermissionMode(null, false);

            $resultItems = array_map(function ($result) use ($showFullSchema) {
                $outSchema = match ($showFullSchema) {
                    UsersApiController::FULL_USER_VIEW_PERMISSIONS => $this->usersApi->userSchema(),
                    UsersApiController::BASIC_USER_VIEW_PERMISSIONS => match ($result["private"]) {
                        true => $this->usersApi->viewPrivateProfileSchema(),
                        false => $this->usersApi->viewProfileSchema(),
                    },
                    default => $this->usersApi->viewProfileSchema(),
                };

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
                    $query->whereText(strtolower($email), ["sortEmail"], SearchQuery::MATCH_WILDCARD);
                }
                $searchableFields[] = "sortEmail";
            }
            if ($query->getQueryParameter("emailDomain") && method_exists($query, "wildPartialMatchTextArray")) {
                $emailDomains = array_map(function (string $emailDomain) {
                    $emailDomain = strtolower($emailDomain);
                    if ($emailDomain[0] !== "@") {
                        $emailDomain = "@" . $emailDomain;
                    }
                    return $emailDomain;
                }, $query->getQueryParameter("emailDomain"));
                $query->wildPartialMatchTextArray($emailDomains, "sortEmail");
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

            if ($ipAddresses = $query->getQueryParameter("ipAddresses")) {
                $userIDs = $this->userModel->getUserIDsForIPAddresses($ipAddresses);
                $query->setFilter("userID", $userIDs);
            }

            $this->applyProfileFieldFilters($query);

            // Only users that are not banned are allowed.
            // Users are indexed with 0 for non-banned, >0 for various ban statuses.
            if (!$query->getQueryParameter("includeBanned")) {
                $query->setFilter("banned", [0]);
            } elseif ($query->getQueryParameter("onlyBanned")) {
                $query->setFilter("banned", [
                    \BanModel::BAN_MANUAL,
                    \BanModel::BAN_AUTOMATIC,
                    \BanModel::BAN_TEMPORARY,
                    \BanModel::BAN_WARNING,
                ]);
            }

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

            if ($dateUpdated = $query->getQueryParameter("dateUpdated")) {
                $query->setDateFilterSchema("dateUpdated", $dateUpdated);
            }

            if (!is_null($query->getQueryParameter("emailConfirmed", null))) {
                $emailConfirmed = (bool) $query->getQueryParameter("emailConfirmed");
                $query->setFilter("emailConfirmed", [$emailConfirmed]);
            }

            // Sorts
            $sort = $query->getQueryParameter("sort", "dateLastActive");
            $sortField = ltrim($sort, "-");
            $direction = $sortField === $sort ? SearchQuery::SORT_ASC : SearchQuery::SORT_DESC;
            switch ($sortField) {
                case "name":
                    $sortField = "sortName.keyword";
                    break;
                case "userID":
                    $sortField = "userID.numeric";
                    break;
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
                "userID:s?",
                "email:s?",
                "emailDomain:a?" => [
                    "items" => [
                        "type" => "string",
                    ],
                ],
                "roleIDs:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                ],
                "emailConfirmed:b?",
                "dateLastActive?" => new DateFilterSchema(),
                "dateUpdated?" => new DateFilterSchema(),
                "sort:s?" => [
                    "enum" => ApiUtils::sortEnum("dateLastActive", "userID", "points"),
                ],
                "profileFields:o?",
                "ipAddresses:a?" => [
                    "items" => [
                        "type" => "string",
                    ],
                ],
                "includeBanned:b?",
                "onlyBanned:b?",
            ],
            $this->schemaFields
        );
        return Schema::parse($schemaFields);
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

        $ipAddresses = $query->getQueryParameter("ipAddresses", []);
        if (!empty($ipAddresses) && !$this->usersApi->checkUserPermissionMode(throw: false)) {
            throw new PermissionException("You don't have permission to search by IP address.");
        }

        if (
            ($query->getQueryParameter("includeBanned") || $query->getQueryParameter("onlyBanned")) &&
            !$this->usersApi->checkUserPermissionMode(throw: false)
        ) {
            throw new PermissionException("You don't have permission to search banned users.");
        }

        foreach ($ipAddresses as $ipAddress) {
            if (
                !str_contains(haystack: $ipAddress, needle: "*") &&
                !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)
            ) {
                throw new ClientException("$ipAddress is not a valid IP address");
            }
        }
        $this->validateProfileFieldFilters($query);
        $this->filterByUserID($query);
    }

    /**
     * @return bool
     */
    private function canSearchEmails(): bool
    {
        return $this->usersApi->checkUserPermissionMode(throw: false) == UsersApiController::FULL_USER_VIEW_PERMISSIONS;
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
            $this->usersApi->checkUserPermissionMode();
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
        $this->profileFieldModel->getProfileFieldFilterSchema()->validate($profileFields);
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
            $profileFields = $this->profileFieldModel->getProfileFieldFilterSchema()->validate($profileFields);
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
                        $query->whereText($value, ["profileFields.$apiName"], SearchQuery::MATCH_FULLTEXT_EXTENDED);
                    }
                    break;
                case [ProfileFieldModel::DATA_TYPE_BOOL, ProfileFieldModel::FORM_TYPE_CHECKBOX]:
                    if ($value) {
                        $query->setFilter("profileFields.$apiName", [true]);
                    } else {
                        $query->setFilter("profileFields.$apiName", [true], filterOp: SearchQuery::FILTER_OP_NOT);
                    }
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

    /**
     * Ability to filter by user ID
     *
     * @param SearchQuery $query
     * @return void
     * @throws ValidationException
     */
    private function filterByUserID(SearchQuery $query): void
    {
        $ipAddresses = $query->getQueryParameter("ipAddresses", null);
        if (empty($ipAddresses)) {
            $userIDs = $query->getQueryParameter("userID", null);
            if (!empty($userIDs)) {
                $userIDs = RangeExpression::createSchema([":int"])->validate($userIDs);
                foreach ($userIDs->getValues() as $op => $userID) {
                    $userID = (int) $userID;
                    switch ($op) {
                        case "=":
                            $query->setFilter("userID.numeric", [$userID]);
                            break;
                        case "<":
                            $query->setFilterRange("userID.numeric", null, $userID, true, false);
                            break;
                        case ">":
                            $query->setFilterRange("userID.numeric", $userID, null, true, false);
                            break;
                        case "<=":
                            $query->setFilterRange("userID.numeric", null, $userID, false, false);
                            break;
                        case ">=":
                            $query->setFilterRange("userID.numeric", $userID, null, false, false);
                            break;
                    }
                }
            }
        }
    }
}
