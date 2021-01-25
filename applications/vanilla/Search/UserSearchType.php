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
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\ServerException;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\PermissionException;
use Vanilla\Search\MysqlSearchQuery;
use Vanilla\Search\SearchQuery;
use Vanilla\Search\AbstractSearchType;
use Vanilla\Utility\ArrayUtils;
use UsersApiController;
use Vanilla\Utility\ModelUtils;

/**
 * Search record type for a user.
 */
class UserSearchType extends AbstractSearchType {

    /** @var UsersApiController $usersApi */
    protected $usersApi;

    /** @var array $schemaFields Additional registered schema properties */
    private $schemaFields = [];

    /** @var array $filters Additional user filters to apply to the search driver */
    private $filters = [];

    /** @var \Gdn_Session */
    private $session;

    /**
     * UserSearchType constructor.
     *
     * @param \UsersApiController $usersApi
     * @param \Gdn_Session $session
     */
    public function __construct(
        UsersApiController $usersApi,
        \Gdn_Session $session
    ) {
        $this->usersApi = $usersApi;
        $this->session = $session;
    }


    /**
     * @inheritdoc
     */
    public function getKey(): string {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function getSearchGroup(): string {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function getResultItems(array $recordIDs, SearchQuery $query): array {
        try {
            $results = $this->usersApi->index([
                'userID' => implode(',', $recordIDs),
                'expand' => [ModelUtils::EXPAND_CRAWL],
            ]);
            $results = $results->getData();

            $showFullSchema = $this->usersApi->checkPermission();
            $outSchema = $showFullSchema ? $this->usersApi->userSchema() : $this->usersApi->viewProfileSchema();

            $resultItems = array_map(function ($result) use ($outSchema) {
                $mapped = ArrayUtils::remapProperties($result, [
                    'recordID' => 'userID',
                ]);
                $mapped['recordType'] = $this->getSearchGroup();
                $mapped['type'] = $this->getType();
                $mapped['userInfo'] = $result;

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
    public function applyToQuery(SearchQuery $query) {
        if ($query instanceof MysqlSearchQuery) {
            $query->addSql($this->generateSql($query));
        } else {
            $query->addIndex($this->getIndex());

            $searchableFields = ['sortName'];
            if ($this->canSearchEmails()) {
                if ($email = $query->getQueryParameter('email')) {
                    $query->whereText($email, ['email'], SearchQuery::MATCH_WILDCARD);
                }
                $searchableFields[] = 'email';
            }

            if ($allFieldText = $query->getQueryParameter('query')) {
                $query->whereText(strtolower($allFieldText), $searchableFields, SearchQuery::MATCH_WILDCARD);
            }

            if ($name = $query->getQueryParameter('name')) {
                $query->whereText(strtolower($name), ['sortName'], SearchQuery::MATCH_WILDCARD);
            }

            if ($roles = $query->getQueryParameter('roleIDs')) {
                $query->setFilter('roles.roleID', $roles);
            }

            // Only users that are not banned are allowed.
            // Users are indexed with 0 for non-banned, >0 for various ban statuses.
            $query->setFilter('banned', [0]);

            foreach ($this->filters as $filterName => $filterField) {
                if ($filterValue = $query->getQueryParameter($filterName, false)) {
                    $query->setFilter($filterField, $filterValue);
                }
            }

            if ($dateInserted = $query->getQueryParameter('dateInserted')) {
                $query->setDateFilterSchema('dateInserted', $dateInserted);
            }

            if ($lastActiveDate = $query->getQueryParameter('dateLastActive')) {
                $query->setDateFilterSchema('dateLastActive', $lastActiveDate);
            }

            // Sorts
            $sort = $query->getQueryParameter('sort', 'dateLastActive');
            $sortField = ltrim($sort, '-');
            $direction = $sortField === $sort ? SearchQuery::SORT_ASC : SearchQuery::SORT_DESC;
            if ($sortField === 'name') {
                $sortField = 'sortName.keyword';
            }

            $query->setSort($direction, $sortField);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array {
        return [

        ];
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchema(): Schema {
        $schemaFields = array_merge(
            [
                'email:s?' => [
                    'x-search-filter' => true,
                ],
                'roleIDs:a?' => [
                    'items' => [
                        'type' => 'integer',
                    ],
                    'x-search-filter' => true,
                ],
                'dateLastActive?' => new DateFilterSchema([
                    'x-search-filter' => true,
                ]),
                "sort:s?" => [
                    "enum" => [
                        "dateLastActive",
                        "-dateLastActive",
                    ],
                ],
            ],
            $this->schemaFields
        );
        return Schema::parse($schemaFields);
    }

    /**
     * @inheritdoc
     */
    public function getQuerySchemaExtension(): Schema {
        return Schema::parse([
            "sort:s?" => [
                "enum" => [
                    "countPosts",
                    "-countPosts",
                    "name",
                    "-name"
                ],
            ],
        ]);
    }

    /**
     * Generates prepares sql query string
     *
     * @param MysqlSearchQuery $query
     * @return string
     */
    public function generateSql(MysqlSearchQuery $query): string {
        // mysql is not implemented
        return '';
    }

    /**
     * @inheritdoc
     */
    public function validateQuery(SearchQuery $query): void {
        $hasSiteScope = $query->getQueryParameter('scope', 'site') === 'site';
        $types = $query->getQueryParameter('types', []);
        $recordTypes = $query->getQueryParameter('recordTypes', []);
        if (!$hasSiteScope && (in_array($this->getType(), $types) || in_array($this->getSearchGroup(), $recordTypes))) {
            throw new ClientException('Cannot search users with any scope other than `site`.', 422);
        }


        if (!$this->canSearchEmails() && $query->getQueryParameter('email', null) !== null) {
            throw new PermissionException("You don't have permission to search by email.");
        }
    }

    /**
     * @return bool
     */
    private function canSearchEmails(): bool {
        return $this->usersApi->checkPermission();
    }

    /**
     * Add fields to schema
     *
     * @param array $schemaFields
     */
    public function addSchemaFields(array $schemaFields) {
        $this->schemaFields = array_merge($this->schemaFields, $schemaFields);
    }

    /**
     * Add filters to apply
     *
     * @param string $filterName
     * @param string $filterField
     */
    public function addFilter(string $filterName, string $filterField) {
        $this->filters[$filterName] = $filterField;
    }

    /**
     * User's should not be searched with other types. Their
     * @inheritdoc
     */
    public function isExclusiveType(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function userHasPermission(): bool {
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
    public function getSingularLabel(): string {
        return \Gdn::translate('User');
    }

    /**
     * @return string
     */
    public function getPluralLabel(): string {
        return \Gdn::translate('Users');
    }

    /**
     * @inheritdoc
     */
    public function getDTypes(): ?array {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function guidToRecordID(int $guid): ?int {
        return null;
    }
}
