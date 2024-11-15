<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Container\ContainerException;
use Garden\Schema\Schema;
use Garden\Schema\Validation;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Dashboard\Models\UserLeaderQuery;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Dashboard\UserLeaderService;
use Vanilla\Dashboard\UserPointsModel;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Search\UserSearchType;
use Vanilla\ImageResizer;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\PermissionFragmentSchema;
use Vanilla\Permissions;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchService;
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;
use Vanilla\PermissionsTranslationTrait;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DelimitedScheme;
use Vanilla\Menu\CounterModel;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Menu\Counter;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;
use Garden\Web\Pagination;

/**
 * API Controller for the `/users` resource.
 */
class UsersApiController extends AbstractApiController
{
    use PermissionsTranslationTrait;

    const ME_ACTION_CONSTANT = "@@users/GET_ME_DONE";
    const PERMISSIONS_ACTION_CONSTANT = "@@users/GET_PERMISSIONS_DONE";
    const ERROR_SELF_EDIT_PASSWORD_MISSING = "The field `passwordConfirmation` is required to edit this profile";
    const ERROR_PATCH_HIGHER_PERMISSION_USER = "You are not allowed to edit a user that has higher permissions than you.";

    // Used to determine level of access current user has to other user profile.
    const FULL_USER_VIEW_PERMISSIONS = 1;
    const BASIC_USER_VIEW_PERMISSIONS = 0;
    const NO_USER_VIEW_PERMISSIONS = -1;

    /** @var ActivityModel */
    private $activityModel;

    /** @var Gdn_Configuration */
    private $configuration;

    /** @var CounterModel */
    private $counterModel;

    /** @var LongRunner */
    private $longRunner;

    /** @var array */
    private $guestFragment;

    /** @var Schema */
    private $idParamSchema;

    /** @var ImageResizer */
    private $imageResizer;

    /** @var UserModel */
    private $userModel;

    /** @var Schema */
    private $userSchema;

    /** @var Schema */
    private $menuCountsSchema;

    /**
     * @var \Vanilla\Web\APIExpandMiddleware
     */
    private $expandMiddleware;

    /** @var ProfileFieldModel */
    private $profileFieldModel;

    /** @var RoleModel */
    private $roleModel;

    private ReactionModel $reactionModel;

    /**
     * UsersApiController constructor.
     *
     * @param UserModel $userModel
     * @param Gdn_Configuration $configuration
     * @param CounterModel $counterModel
     * @param ImageResizer $imageResizer
     * @param ActivityModel $activityModel
     * @param \Vanilla\Web\APIExpandMiddleware $expandMiddleware
     * @param ProfileFieldModel $profileFieldModel
     * @param RoleModel $roleModel
     * @param ReactionModel $reactionModel
     * @param LongRunner $longRunner
     */
    public function __construct(
        UserModel $userModel,
        Gdn_Configuration $configuration,
        CounterModel $counterModel,
        ImageResizer $imageResizer,
        ActivityModel $activityModel,
        \Vanilla\Web\APIExpandMiddleware $expandMiddleware,
        ProfileFieldModel $profileFieldModel,
        RoleModel $roleModel,
        ReactionModel $reactionModel,
        LongRunner $longRunner
    ) {
        $this->configuration = $configuration;
        $this->counterModel = $counterModel;
        $this->userModel = $userModel;
        $this->imageResizer = $imageResizer;
        $this->nameScheme = new DelimitedScheme(".", new CamelCaseScheme());
        $this->activityModel = $activityModel;
        $this->expandMiddleware = $expandMiddleware;
        $this->profileFieldModel = $profileFieldModel;
        $this->roleModel = $roleModel;
        $this->reactionModel = $reactionModel;
        $this->longRunner = $longRunner;
    }

    /**
     * Delete a user.
     *
     * @param int $id The ID of the user.
     * @param array $body The request body.
     * @throws NotFoundException if the user could not be found.
     */
    public function delete($id, array $body = [])
    {
        $this->permission("Garden.Users.Delete");

        $this->idParamSchema()->setDescription("Delete a user.");

        $in = $this->schema(
            [
                "deleteMethod:s?" => [
                    "description" => "The deletion method / strategy.",
                    "enum" => ["keep", "wipe", "delete"],
                    "default" => "delete",
                ],
            ],
            "in"
        );
        $out = $this->schema([], "out");
        $body = $in->validate($body);

        $this->userByID($id);
        $this->userModel->deleteID($id, ["DeleteMethod" => $body["deleteMethod"]]);
    }

    /**
     * Delete a user photo.
     *
     * @param int|null $id The ID of the user.
     * @throws ClientException if the user does not have a photo.
     */
    public function delete_photo($id = null)
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->idParamSchema()->setDescription("Delete a user photo.");
        $out = $this->schema([], "out");

        if ($id === null) {
            $id = $this->getSession()->UserID;
        }

        $user = $this->userByID($id);

        if ($id !== $this->getSession()->UserID) {
            $this->permission("Garden.Users.Edit");
        }

        if (empty($user["Photo"])) {
            throw new ClientException("The user does not have a photo.");
        }

        $this->userModel->removePicture($id);
    }

    /**
     * Get a schema instance comprised of all available user fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema()
    {
        $result = $this->userModel->schema();
        return $result;
    }

    /**
     * Get the schema for menu item counts.
     *
     * @return Schema Returns a schema.
     */
    public function getMenuCountsSchema()
    {
        if ($this->menuCountsSchema === null) {
            $this->menuCountsSchema = $this->schema(
                [
                    "counts:a?" => new InstanceValidatorSchema(Counter::class),
                ],
                "MenuCounts"
            );
        }
        return $this->menuCountsSchema;
    }

    /**
     * Check if current session user has just Profile.View permission
     * or advanced permissions as well returns (BASIC_USER_VIEW_PERMISSIONS) for full permissions, BASIC_USER_VIEW_PERMISSIONS for profile.view,
     * NO_USER_VIEW_PERMISSIONS for no permissions, or throws Exception if $throw is set to true.
     *
     * @param string|null $permissionToCheck The permissions you are requiring.
     * @param bool $throw Whether to throw an exception if the user does not have the specified permission(s).
     * @return int
     */
    public function checkUserPermissionMode(string $permissionToCheck = null, bool $throw = true): int
    {
        $session = $this->getSession();
        $showFullSchema = self::BASIC_USER_VIEW_PERMISSIONS;
        $permissions = ["Garden.Users.Add", "Garden.Users.Edit", "Garden.Users.Delete", "Garden.PersonalInfo.View"];
        if ($permissionToCheck !== null) {
            $permissions[] = $permissionToCheck;
        }

        if ($session->checkPermission($permissions, false)) {
            $showFullSchema = self::FULL_USER_VIEW_PERMISSIONS;
        } else {
            $permissions = ["Garden.Profiles.View"];
            if ($permissionToCheck !== null) {
                $permissions[] = $permissionToCheck;
            }
            if ($throw) {
                $this->permission($permissions);
            } else {
                $showFullSchema = $session->checkPermission($permissions)
                    ? self::BASIC_USER_VIEW_PERMISSIONS
                    : self::NO_USER_VIEW_PERMISSIONS;
            }
        }

        return $showFullSchema;
    }

    /**
     * Get a single user.
     *
     * @param int $id The ID of the user.
     * @param array $query The request query.
     * @return Data
     * @throws ServerException If session isn't found.
     * @throws NotFoundException If the user could not be found.
     */
    public function get(int $id, array $query): Data
    {
        $this->permission(Permissions::BAN_ROLE_TOKEN);
        $isSelf = $id === $this->getSession()->UserID;
        $showFullSchema = $isSelf
            ? self::FULL_USER_VIEW_PERMISSIONS
            : $this->checkUserPermissionMode(Permissions::BAN_ROLE_TOKEN, false);
        $queryIn = $this->schema(
            ["expand?" => ApiUtils::getExpandDefinition(["profileFields", "discoveryText", "reactionsReceived"])],
            ["UserGet", "in"]
        )->setDescription("Get a user.");

        $this->idParamSchema();
        $query = $queryIn->validate($query);
        $expand = $query["expand"] ?? [];
        $row = $this->userByID($id);
        $outSchema = match ($showFullSchema) {
            self::FULL_USER_VIEW_PERMISSIONS => $this->userSchema(),
            self::BASIC_USER_VIEW_PERMISSIONS => $this->viewProfileSchema(),
            default => match ($row["Attributes"]["Private"]) {
                "0" => $this->viewProfileSchema(),
                default => $this->viewPrivateProfileSchema(),
            },
        };

        $outSchema = CrawlableRecordSchema::applyExpandedSchema($outSchema, "user", $expand);
        if (
            $this->getSession()->checkPermission("Garden.Profiles.View") &&
            ModelUtils::isExpandOption("discoveryText", $expand)
        ) {
            $outSchema->merge(Schema::parse(["discoveryText:s?"]));
        }
        $out = $this->schema($outSchema, "out");
        $row = $this->normalizeOutput($row, $expand);

        $showEmail = $row["showEmail"] ?? false;
        if (!$showEmail && !$showFullSchema) {
            unset($row["email"]);
        }

        if (!$showFullSchema) {
            $this->userModel->filterPrivateUserRecord($row);
        }
        if (ModelUtils::isExpandOption("reactionsReceived", $expand)) {
            [$row] = $this->reactionModel->expandUserReactionsReceived([$row]);
        }
        $result = $out->validate($row);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter(
            "usersApiController_getOutput",
            $result,
            $this,
            $queryIn,
            $query,
            $row
        );

        return new Data($result, ["api-allow" => ["email"]]);
    }

    /**
     * Get a user for editing.
     *
     * @param int $id The ID of the user.
     * @throws NotFoundException if the user could not be found.
     * @return Data
     */
    public function get_edit($id)
    {
        $this->permission("Garden.Users.Edit");

        $in = $this->idParamSchema()->setDescription("Get a user for editing.");
        $out = $this->schema(
            Schema::parse(["userID", "name", "email", "showEmail", "photo", "emailConfirmed", "bypassSpam"])->add(
                $this->fullSchema()
            ),
            "out"
        );

        $row = $this->userByID($id);

        $result = $out->validate($row);
        $result = new Data($result, ["api-allow" => ["email"]]);
        return $result;
    }

    /**
     * Get global permissions available to the current user.
     *
     * @return array
     */
    private function globalPermissions(): array
    {
        $result = [];

        foreach ($this->getSession()->getPermissionsArray() as $permission) {
            if (!is_string($permission)) {
                continue;
            }
            $result[] = $this->renamePermission($permission);
        }

        sort($result);
        return $result;
    }

    /**
     * Get a user fragment representing a guest.
     *
     * @return array
     */
    public function getGuestFragment()
    {
        if ($this->guestFragment === null) {
            $this->guestFragment = [
                "userID" => 0,
                "name" => t("Guest"),
                "photoUrl" => UserModel::getDefaultAvatarUrl(),
                "dateLastActive" => null,
                "isAdmin" => false,
                "isSysAdmin" => false,
                "isSuperAdmin" => false,
            ];
        }
        return $this->guestFragment;
    }

    /**
     * Get a list of users, filtered by username.
     *
     * @param array $query
     * @return Data
     */
    public function index_byNames(array $query)
    {
        $this->permission();

        $in = $this->schema(
            [
                "name:s" =>
                    "Filter for username. Supports full or partial matching with appended wildcard (e.g. User*).",
                "order:s?" => [
                    "description" => "Sort method for results.",
                    "enum" => ["countComments", "dateLastActive", "name", "mention"],
                    "default" => "name",
                ],
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                ],
                "limit:i?" => [
                    "description" => "Desired number of items per page.",
                    "default" => 30,
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(),
                ],
            ],
            "in"
        )->setDescription("Search for users by full or partial name matching.");
        $out = $this->schema(
            [
                ":a" => $this->getUserFragmentSchema(),
            ],
            "out"
        );

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        if ($query["order"] == "mention") {
            [$sortField, $sortDirection] = $this->userModel->getMentionsSort();
        } else {
            $sortField = $query["order"];
            switch ($sortField) {
                case "countComments":
                case "dateLastActive":
                    $sortDirection = "desc";
                    break;
                case "name":
                default:
                    $sortDirection = "asc";
            }
        }

        $rows = $this->userModel
            ->searchByName($query["name"], $sortField, $sortDirection, $limit, $offset)
            ->resultArray();

        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $this->userModel->filterPrivateUserRecord($rows);
        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/users/names", $query, $in);

        return new Data($result, ["paging" => $paging]);
    }

    /**
     * Get a user's permissions.
     *
     * @param int $id The user's ID.
     * @param array $query Query parameters.
     *
     * @return Data
     */
    public function get_permissions(int $id, array $query = []): Data
    {
        $requestedUserID = $id;
        $this->permission();
        $in = Schema::parse([
            "expand?" => ApiUtils::getExpandDefinition(["junctions"]),
        ]);
        $out = $this->schema([
            "isAdmin:b",
            "isSysAdmin:b",
            "isSuperAdmin:b",
            "permissions:a" => new PermissionFragmentSchema(),
            "junctions?",
            "junctionAliases?",
        ]);

        $query = $in->validate($query);

        if (is_object($this->getSession()->User)) {
            $sessionUser = (array) $this->getSession()->User;
            $sessionUser = $this->normalizeOutput($sessionUser);
        } else {
            $sessionUser = $this->getGuestFragment();
        }

        // If it's not our own user, then we need to check for a stronger permissions.

        if ($sessionUser["userID"] !== $requestedUserID) {
            $this->permission(["Garden.Users.Add", "Garden.Users.Edit", "Garden.Users.Delete"]);
        }

        // Build the permissions
        // This endpoint is heavily used (every page request), so we rely on caching in the model.
        $permissions = $this->userModel->getPermissions($requestedUserID);

        $result = $permissions->asApiOutput(ModelUtils::isExpandOption("junctions", $query["expand"] ?? []));
        $result = $out->validate($result);

        return Data::box($result);
    }

    /**
     * Get a fragment representing the current user.
     *
     * @param array $query
     * @return Data
     * @throws ValidationException If output validation fails.
     * @throws \Garden\Web\Exception\HttpException If a ban has been applied on the permission(s) for this session.
     * @throws \Vanilla\Exception\PermissionException If the user does not have valid permission to access this action.
     */
    public function get_me(array $query)
    {
        $this->permission();

        $in = $this->schema([], "in")->setDescription("Get information about the current user.");
        $out = $this->schema(
            Schema::parse([
                "userID",
                "name",
                "photoUrl",
                "email:s|n" => ["default" => null],
                "emailConfirmed:b" => [
                    "default" => false,
                ],
                "dateLastActive",
                "isAdmin:b",
                "isSysAdmin:b",
                "isSuperAdmin:b",
                "countUnreadNotifications" => [
                    "description" => "Total number of unread notifications for the current user.",
                    "type" => "integer",
                ],
                "countUnreadConversations" => [
                    "description" => "Total number of unread conversations for the current user.",
                    "type" => "integer",
                ],
                "permissions" => [
                    "description" => "Global permissions available to the current user.",
                    "items" => [
                        "type" => "string",
                    ],
                    "type" => "array",
                ],
                "suggestAnswers:b?",
            ])->add($this->getUserFragmentSchema()),
            "out"
        );

        $query = $in->validate($query);

        if (is_object($this->getSession()->User)) {
            $user = (array) $this->getSession()->User;
            $user = $this->normalizeOutput($user);
        } else {
            $user = $this->getGuestFragment();
        }

        // Expand permissions for the current user.
        $user["permissions"] = $this->globalPermissions();
        $user["countUnreadNotifications"] = $this->activityModel->getUserTotalUnread($this->getSession()->UserID);
        $user["countUnreadConversations"] = $user["countUnreadConversations"] ?? 0;
        $result = $out->validate($user);

        $response = $this->expandMiddleware->updateResponseByKey($result, "userID", true, ["users"]);
        $response->setMeta(\Vanilla\Web\ApiFilterMiddleware::FIELD_ALLOW, ["email"]);
        $response->setHeader(self::HEADER_CACHE_CONTROL, self::NO_CACHE);

        return $response;
    }

    /**
     * Get all menu counts for current user.
     *
     * @return array
     */
    public function get_meCounts(): array
    {
        $this->permission();

        $in = $this->schema([], "in");
        $out = $this->schema($this->getMenuCountsSchema(), "out");

        $counters = $this->counterModel->getAllCounters();

        $result = $out->validate(["counts" => $counters]);
        return $result;
    }

    /**
     * Get an ID-only user record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = "in")
    {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(Schema::parse(["id:i" => "The user ID."]), $type);
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List users.
     *
     * @param array $query The query string.
     * @return Data
     * @throws ClientException
     * @throws ServerException
     * @throws ValidationException
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function index(array $query): Data
    {
        // This is to support the older profile field filter name
        $query["profileFields"] ??= $query["extended"] ?? null;
        $result = $this->tryWithUserSearch($query);
        if ($result instanceof Data) {
            return $result;
        }
        $this->permission(Permissions::BAN_ROLE_TOKEN);
        $showFullSchema = $this->checkUserPermissionMode(null, false);

        $in = $this->schema(
            [
                "dateInserted?" => new DateFilterSchema([
                    "description" => "When the user was created.",
                    "x-filter" => [
                        "field" => "u.DateInserted",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "dateUpdated?" => new DateFilterSchema([
                    "description" => "When the user was updated.",
                    "x-filter" => [
                        "field" => "u.DateUpdated",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "dateLastActive?" => new DateFilterSchema([
                    "x-filter" => [
                        "field" => "u.DateLastActive",
                        "processor" => [DateFilterSchema::class, "dateFilterField"],
                    ],
                ]),
                "roleID:i?" => [
                    "x-filter" => ["field" => "roleID"],
                ],
                "roleIDs:a?" => [
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-filter" => ["field" => "roleIDs"],
                ],
                "userID?" => \Vanilla\Schema\RangeExpression::createSchema([":int"])->setField("x-filter", [
                    "field" => "u.UserID",
                ]),
                "ipAddresses:a?" => [
                    "items" => [
                        "type" => "string",
                    ],
                    "x-filter" => ["field" => "ipAddresses"],
                ],
                "emailConfirmed:b?" => [
                    "x-filter" => ["field" => "u.Confirmed"],
                ],
                "page:i?" => [
                    "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                    "default" => 1,
                    "minimum" => 1,
                ],
                "dirtyRecords:b?",
                "sort:s?" => [
                    "enum" => ApiUtils::sortEnum(
                        "dateInserted",
                        "dateLastActive",
                        "name",
                        "userID",
                        "points",
                        "countPosts"
                    ),
                ],
                "limit:i?" => [
                    "description" => "Desired number of items per page.",
                    "default" => 30,
                    "minimum" => 1,
                    "maximum" => 5000,
                ],
                "profileFields:o?" => $this->profileFieldModel->getProfileFieldFilterSchema(),
                "expand?" => ApiUtils::getExpandDefinition(["profileFields", "discoveryText", "reactionsReceived"]),
            ],
            ["UserIndex", "in"]
        )
            ->addValidator("roleIDs", function ($data, $field) {
                RoleModel::roleViewValidator($data, $field);
            })
            ->addValidator("", SchemaUtils::onlyOneOf(["dateInserted", "dateUpdated", "roleID"]))
            ->addValidator("ipAddresses", $this->createIpAddressesValidator());

        $query = $in->validate($query);
        $expand = $query["expand"] ?? [];

        $outSchema = match ($showFullSchema) {
            self::FULL_USER_VIEW_PERMISSIONS => $this->userSchema(),
            self::BASIC_USER_VIEW_PERMISSIONS => $this->viewProfileSchema(),
            default => $this->viewPrivateProfileSchema(),
        };
        $outSchema = CrawlableRecordSchema::applyExpandedSchema($outSchema, "user", $expand);
        if (
            is_array($expand) &&
            $this->getSession()->checkPermission("Garden.Profiles.View") &&
            (in_array("discoveryText", $expand) || in_array(ModelUtils::EXPAND_ALL, $expand))
        ) {
            $outSchema->merge(Schema::parse(["discoveryText:s?"]));
        }

        $out = $this->schema([":a" => $outSchema], "out");

        $where = ApiUtils::queryToFilters($in, $query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $joinDirtyRecords = $query[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($joinDirtyRecords) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = $query[DirtyRecordModel::DIRTY_RECORD_OPT];
        }
        if (isset($query["profileFields"])) {
            $where["profileFields"] = $query["profileFields"];
        }

        $rows = $this->userModel->search($where, $query["sort"] ?? "", "", $limit, $offset)->resultArray();

        // Join in the roles more efficiently for the index.
        // Attempting to join roles from cache works well for single records where a user might be coming back over and over,
        // But isn't really appropriate for iterating over lists of users where the same user will not likely be seen twice.
        // Fetch all roles at once.
        $this->userModel->joinRoles($rows);

        foreach ($rows as &$row) {
            $this->userModel->setCalculatedFields($row);
            $row = $this->normalizeOutput($row, $expand);
            $showEmail = $row["showEmail"] ?? false;
            if (!$showEmail && !$showFullSchema) {
                unset($row["email"]);
            }
        }

        if ($showFullSchema !== self::FULL_USER_VIEW_PERMISSIONS) {
            $this->userModel->filterPrivateUserRecord($rows);
        }
        if (ModelUtils::isExpandOption("reactionsReceived", $expand)) {
            $rows = $this->reactionModel->expandUserReactionsReceived($rows);
        }

        $result = $out->validate($rows);

        if ($joinDirtyRecords) {
            $this->userModel->applyDirtyWheres("u");
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
        }
        $totalCount = $this->userModel->searchCount($where);

        if (!ModelUtils::isExpandOption("crawl", $query["expand"])) {
            $paging = ApiUtils::numberedPagerInfo($totalCount, "/api/v2/users", $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($result, "/api/v2/users", $query, $in);
        }

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter(
            "usersApiController_indexOutput",
            $result,
            $this,
            $in,
            $query,
            $rows
        );
        $meta = Pagination::tryCursorPagination($paging, $query, $result, "userID") + ["api-allow" => ["email"]];

        return new Data($result, $meta);
    }

    /**
     * Returns a function for use by Schema::addValidator() to validate an array of IP addresses.
     *
     * @return Closure
     */
    public function createIpAddressesValidator(): Closure
    {
        return function (array $ipAddresses, \Garden\Schema\ValidationField $field) {
            if ($this->checkUserPermissionMode() !== self::FULL_USER_VIEW_PERMISSIONS) {
                $field->addError("You don't have permission to filter by IP address", ["status" => 403]);
            }
            foreach ($ipAddresses as $ipAddress) {
                if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                    $field->addError("$ipAddress is not a valid IP address");
                }
            }
        };
    }

    /**
     * Attempt to perform the query using search service.
     *
     * This method checks if the query can be handled using the SearchService (if certain keys exist in the query). If
     * it can be handled through the search service, and the UserSearch type is available, the query is passed to the
     * SearchService::search() method and the return value is wrapped in a Data object and returned to the caller.
     *
     * @param array $query The original query.
     * @return Data|null
     * @throws ClientException|ServerException|ValidationException|ContainerException|\Garden\Container\NotFoundException
     */
    private function tryWithUserSearch(array $query): ?Data
    {
        // If there are profile field filters, get only the filters that require elastic search.
        $profileFields = $this->profileFieldModel->getSearchFilters($query["profileFields"] ?? null);
        $filteredQuery = ["profileFields" => $profileFields] + $query;
        $searchFilters = $this->filterNonEmptyValues($filteredQuery, [
            "name",
            "email",
            "query",
            "profileFields",
            "emailDomain",
        ]);
        $searchFilters = ArrayUtils::arrayToDotNotation($searchFilters);

        // If we don't have any search-only filters, break out of here.
        if (empty($searchFilters)) {
            return null;
        }

        $searchFilters = implode(", ", array_keys($searchFilters));

        // Need to initialize search service here because of cyclical dependency.
        $searchService = Gdn::getContainer()->get(SearchService::class);
        $driver = $searchService->getActiveDriver($query["driver"] ?? null);

        if (is_null($driver->getSearchTypeByType(UserSearchType::TYPE))) {
            throw new ClientException("The following filters require the user search type: $searchFilters");
        }
        if (isset($query["query"]) && isset($query["domain"])) {
            throw new ClientException("query cannot be combined with the domain filter.");
        }

        foreach (["dirtyRecords", "userID", "roleID"] as $filter) {
            if (isset($query[$filter])) {
                throw new ClientException("$filter cannot be combined with the following: $searchFilters");
            }
        }

        $in = $searchService->buildQuerySchema();
        // Increase our limit
        $in->setField("properties.limit.maximum", 5000);

        $originalQuery = $query;

        // Add some specific filters
        // Make sure we don't exclude banned users (default behaviour of member search).
        $query["includeBanned"] = true;

        $query = $in->validate(["recordTypes" => [UserSearchType::TYPE]] + $query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        // We're not validating limits here.
        // That should have been handled at an API level.
        $searchQuery = $query;
        unset($searchQuery["limit"]);

        // Clear off our limit, we don't want
        $searchResults = $driver->search($searchQuery, new SearchOptions($offset, $limit, true));

        // Call UsersApiController::index() with original `expand` parameter using recordIDs as userIDs
        $userIDs = array_map(function ($resultItem) {
            return $resultItem["recordID"] ?? null;
        }, $searchResults->getResultItems());
        $finalQuery = ["userID" => $userIDs, "limit" => $limit];
        if (isset($originalQuery["expand"])) {
            $finalQuery["expand"] = $originalQuery["expand"];
        }
        if (isset($originalQuery["sort"])) {
            $finalQuery["sort"] = $originalQuery["sort"];
        }

        // Don't allow this to propagate into our paging urls.
        $requestHadCursor = isset($query["cursor"]);
        unset($query["cursor"]);
        $rows = !empty($userIDs) ? $this->index($finalQuery)->getData() : [];
        $paging = ApiUtils::numberedPagerInfo(
            $searchResults->getTotalCount(),
            "/api/v2/users",
            $query,
            $in,
            $searchResults->getCursor()
        );
        if (($requestHadCursor && $searchResults->getCursor() === null) || $searchResults->getResultCount() < $limit) {
            // If we are paginating with the cursor, stop paginating when the cursor runs out.
            $paging["page"] = $paging["pageCount"];
        }
        return new Data($rows, ["paging" => $paging, "api-allow" => ["email"]]);
    }

    /**
     * Helper method to filter elements from the query array if they contain empty strings or arrays of empty strings.
     *
     * @param array $query
     * @param array $keys
     * @return array
     */
    private function filterNonEmptyValues(array $query, array $keys): array
    {
        return array_filter(ArrayUtils::pluck($query, $keys), function ($field) {
            // Filter out fields if they are empty.
            if ($field === "") {
                return false;
            }
            // Filter out fields if they are arrays and don't contain non-empty strings.
            if (is_array($field) && count(array_filter($field, "strlen")) === 0) {
                return false;
            }
            return true;
        });
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|string|bool $expand
     *
     * @return array Return a Schema record.
     */
    protected function normalizeOutput(array $dbRecord, $expand = [])
    {
        $result = $this->userModel->normalizeRow($dbRecord, $expand);
        return $result;
    }

    /**
     * Update a user.
     *
     * @param int $id The ID of the user.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the user.
     * @return Data
     */
    public function patch($id, array $body)
    {
        $this->idParamSchema("in");
        $user = $this->userByID($id);
        $userPermissions = $this->userModel->getPermissions($id);

        // Ensure
        $rankCompare = Gdn::session()
            ->getPermissions()
            ->compareRankTo($userPermissions);
        if ($id !== $this->getSession()->UserID && $rankCompare < 0) {
            throw new \Garden\Web\Exception\ForbiddenException(t(self::ERROR_PATCH_HIGHER_PERMISSION_USER));
        }

        $this->permission(Permissions::BAN_ROLE_TOKEN);
        $this->idParamSchema("in");
        if ($this->checkUserPermissionMode() === self::FULL_USER_VIEW_PERMISSIONS) {
            $in = $this->schema($this->userPatchSchema(), ["UserPatchCommon", "in"])->setDescription("Update a user.");
        } else {
            $in = $this->schema($this->userPatchSelfEditSchema(), ["UserPatchCommon", "in"])->setDescription(
                "Update a user."
            );
        }
        if ($id == $this->getSession()->UserID) {
            $in->merge(Schema::parse(["passwordConfirmation:s?"]));
        }
        $in->addValidator("roleID", $this->createRoleIDValidator($id));
        $in->addValidator("profileFields", $this->profileFieldModel->validateEditable($id));

        $out = $this->userSchema("out");
        $body = $in->validate($body);

        // Check for self-edit
        if ($id == $this->getSession()->UserID) {
            $this->validatePatchSelfEditCredentials($user, $body);
            unset($body["passwordConfirmation"]);
        } else {
            $this->permission("Garden.Users.Edit");
        }

        $userData = $this->normalizeInput($body);
        $userData["UserID"] = $id;
        $settings = ["ValidateName" => false];
        if (!empty($userData["RoleID"])) {
            $settings["SaveRoles"] = true;
        }

        if (isset($userData["ResetPassword"]) ?? false) {
            $userData["HashMethod"] = "Reset";
            $settings["ResetPassword"] = true;
        }

        $existingUser = $this->userByID($id);
        if (isset($userData["Banned"]) && $userData["Banned"] != $existingUser["Banned"]) {
            $this->performBan($id, $userData["Banned"]);
        }

        $this->userModel->save($userData, $settings);
        $this->validateModel($this->userModel);
        $row = $this->userByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter(
            "usersApiController_patchOutput",
            $result,
            $this,
            $in,
            $body,
            $row
        );
        $result = new Data($result, ["api-allow" => ["email"]]);
        return $result;
    }

    /**
     * Add a user.
     *
     * @param array $body The request body.
     * @throws ServerException if the user could not be added.
     * @return Data
     */
    public function post(array $body)
    {
        $this->permission("Garden.Users.Add");

        $in = $this->schema($this->userPostSchema(), "in")->setDescription("Add a user.");
        $in->addValidator("roleID", $this->createRoleIDValidator());
        $in->addValidator("profileFields", $this->profileFieldModel->validateEditable());
        $out = $this->schema($this->userSchema(), "out");

        $body = $in->validate($body);

        $userData = $this->normalizeInput($body);
        $settings = [
            UserModel::OPT_NO_CONFIRM_EMAIL => true,
            "SaveRoles" => array_key_exists("RoleID", $userData),
            "ValidateName" => false,
        ];
        $id = $this->userModel->save($userData, $settings);
        if ($id && $body["sendWelcomeEmail"] ?? true) {
            $this->userModel->sendWelcomeEmail($id, $userData["Password"] ?? "");
        }
        // Add default preferences for the new user
        $userNotificationPrefsModel = Gdn::getContainer()->get(UserNotificationPreferencesModel::class);
        $userNotificationPrefsModel->setInitialDefaults($id, true);

        $this->validateModel($this->userModel);

        if (!$id) {
            throw new ServerException("Unable to add user.", 500);
        }

        $row = $this->userByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        $result = new Data($result, ["api-allow" => ["email"]]);
        return $result;
    }

    /**
     * Set a new photo on a user.
     *
     * @param ?int $id A valid user ID.
     * @param array $body The request body.
     * @param RequestInterface|null $request
     * @return array
     */
    public function post_photo($id = null, array $body = [], RequestInterface $request = null)
    {
        $this->permission("Garden.SignIn.Allow");

        $photoUploadSchema = new UploadedFileSchema([
            "allowedExtensions" => ImageResizer::getAllExtensions(),
        ]);

        $in = $this->schema(
            [
                "photo" => $photoUploadSchema,
            ],
            "in"
        );
        $out = $this->schema(Schema::parse(["photoUrl"])->add($this->fullSchema()), "out");

        if ($id === null) {
            $id = $this->getSession()->UserID;
        }

        $this->userByID($id);

        if ($id !== $this->getSession()->UserID) {
            $this->permission("Garden.Users.Edit");
        }
        if ($request !== null) {
            UploadedFileSchema::validateUploadSanity($body, "photo", $request);
        }
        $body = $in->validate($body);

        $photo = $this->processPhoto($body["photo"]);
        $this->userModel->removePicture($id);
        $this->userModel->save(["UserID" => $id, "Photo" => $photo]);

        $user = $this->userByID($id);
        $user = $this->normalizeOutput($user);

        $result = $out->validate($user);
        return $result;
    }

    /**
     * Submit a new user registration.
     *
     * @param array $body The request body.
     * @throws ClientException if terms of service field is false.
     * @throws ServerException if an unknown error was encountered when creating the user.
     * @throws ValidationException if the registration is flagged as SPAM, but no discoveryText was provided.
     * @return Data
     */
    public function post_register(array $body)
    {
        $this->permission([\Vanilla\Permissions::BAN_CSRF, \Vanilla\Permissions::BAN_PRIVATE]);

        $registrationMethod = $this->configuration->get("Garden.Registration.Method");
        $registrationMethod = strtolower($registrationMethod);

        $userData = ApiUtils::convertInputKeys($body);

        $email = c("Garden.Registration.NoEmail", false) ? "email:s?" : "email:s";

        $inputProperties = [
            $email => "An email address for this user.",
            "name:s" => "The username.",
            "password:s" => "A password for this user.",
            "discoveryText:s?" =>
                "Why does the user wish to join? Only used when the registration is flagged as SPAM (response code: 202).",
        ];
        if ($registrationMethod === "invitation") {
            $inputProperties["invitationCode:s"] = "An invitation code for registering on the site.";
        } elseif ($this->userModel->isRegistrationSpam($userData)) {
            // SPAM detected. Require a reason to join.
            $inputProperties["discoveryText:s"] = $inputProperties["discoveryText:s?"];
            unset($inputProperties["discoveryText:s?"]);
        }

        $in = $this->schema($inputProperties, "in")->setDescription("Submit a new user registration.");
        $out = $this->schema(Schema::parse(["userID", "name", "email"])->add($this->fullSchema()), "out");

        $in->validate($body);

        switch ($registrationMethod) {
            case "invitation":
                $userID = $this->userModel->insertForInvite($userData);
                break;
            case "basic":
            case "captcha":
                $userID = $this->userModel->insertForBasic($userData);
                break;
            default:
                throw new ClientException("Unsupported registration method.");
        }
        $this->validateModel($this->userModel);

        if (!$userID) {
            throw new ServerException("An unknown error occurred while attempting to create the user.", 500);
        } elseif ($userID === UserModel::REDIRECT_APPROVE) {
            // A registration has been flagged for approval. Indicate the request has been accepted, but a user hasn't necessarily been created.
            $result = new Data([], 202);
        } else {
            $row = $this->userByID($userID);
            $result = $out->validate($row);
            $result = new Data($result, 201);
        }
        $result = new Data($result, ["api-allow" => ["email"]]);
        return $result;
    }

    /**
     * Ban a user.
     *
     * @param int $id The ID of the user.
     * @param array $body The request body.
     * @throws NotFoundException If unable to find the user.
     * @throws \Garden\Web\Exception\ForbiddenException If the user doesn't have permission to ban the user.
     * @return array
     */
    public function put_ban($id, array $body)
    {
        $this->permission(["Garden.Moderation.Manage", "Garden.Users.Edit", "Moderation.Users.Ban"]);

        $this->idParamSchema("in");
        $in = $this->schema(["banned:b" => "Pass true to ban or false to unban."], "in")->setDescription("Ban a user.");
        $out = $this->schema(["banned:b" => "The current banned value."], "out");

        $row = $this->userByID($id);
        $body = $in->validate($body);

        $this->performBan($id, $body["banned"]);

        $result = $this->userByID($id);
        return $out->validate($result);
    }

    /**
     * Send a password reset email.
     *
     * @param array $body The POST body.
     * @throws Exception Throws all exceptions to the dispatcher.
     */
    public function post_requestPassword(array $body)
    {
        $this->permission(\Vanilla\Permissions::BAN_PRIVATE);

        $in = $this->schema([
            "email:s" => "The email/username of the user.",
        ]);
        $out = $this->schema([], "out");

        $body = $in->validate($body);

        $result = $this->userModel->passwordRequest($body["email"], ["checkCaptcha" => false]);
        if (!$result) {
            $this->validateModel($this->userModel, true);
        }
    }
    /**
     * Confirm a user email address after registration.
     *
     * @param int $id The ID of the user.
     * @param array $body The POST body.
     * @throws ClientException if email has been confirmed.
     * @throws Exception if confirmationCode doesn't match.
     * @throws NotFoundException if unable to find the user.
     * @return Data
     */
    public function post_confirmEmail($id, array $body)
    {
        $this->permission(\Vanilla\Permissions::BAN_CSRF);

        $this->idParamSchema("in");
        $in = $this->schema(
            [
                "confirmationCode:s" => "Email confirmation code",
            ],
            "in"
        )->setDescription("Confirm a users current email address by using a confirmation code");
        $out = $this->schema(["userID:i", "email:s", "emailConfirmed:b"], "out");

        $row = $this->userByID($id);
        if ($row["Confirmed"]) {
            throw new ClientException("This email has already been confirmed");
        }

        $body = $in->validate($body);
        $this->userModel->confirmEmail($row, $body["confirmationCode"]);
        $this->validateModel($this->userModel);

        $result = $out->validate($this->userByID($id));
        $result = new Data($result, ["api-allow" => ["email"]]);
        return $result;
    }

    /**
     * Get a list of leaders by slotType/categoryID/limit criteria.
     *
     * @param array $query
     * @return array
     * @throws \Garden\Web\Exception\HttpException Exception.
     * @throws \Vanilla\Exception\PermissionException Permission Exception.
     */
    public function get_leaders(array $query = []): array
    {
        $this->permission();
        // Inbound data schema validation.
        $schema = $this->schema([
            "leaderboardType:s" => [
                "description" => "Type of data to use for a leaderboard.",
                "enum" => [
                    UserLeaderService::LEADERBOARD_TYPE_POSTS,
                    UserLeaderService::LEADERBOARD_TYPE_REPUTATION,
                    UserLeaderService::LEADERBOARD_TYPE_ACCEPTED_ANSWERS,
                ],
            ],
            "slotType:s" => [
                "description" => 'Slot type ("d" = day, "w" = week, "m" = month, "y" = year, "a" = all).',
                "enum" => ["d", "w", "m", "y", "a"],
            ],
            "categoryID:i?" => [
                "description" => "The numeric ID of a category to limit search results to.",
            ],
            "siteSectionID:s?" => [
                "description" => "The numeric ID of a subcommunity to limit search results to.",
            ],
            "limit:i?" => [
                "description" => "The maximum amount of records to be returned.",
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(),
            ],
            "includedRoleIDs?" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
            "excludedRoleIDs?" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
        ]);

        $query = $schema->validate($query);

        $categoryID = $query["categoryID"] ?? null;
        $userLeaderService = Gdn::getContainer()->get(UserLeaderService::class);

        $query = new UserLeaderQuery(
            $query["slotType"] ?? UserPointsModel::SLOT_TYPE_ALL,
            $query["categoryID"] ?? null,
            $query["siteSectionID"] ?? null,
            $query["limit"] ?? null,
            isset($query["includedRoleIDs"]) ? (array) $query["includedRoleIDs"]->getValue("=") : null,
            isset($query["excludedRoleIDs"]) ? (array) $query["excludedRoleIDs"]->getValue("=") : null,
            $query["leaderboardType"] ?? UserLeaderService::LEADERBOARD_TYPE_REPUTATION
        );
        $leaders = $userLeaderService->getLeaders($query);

        // Outbound data schema validation.
        $leaders = $this->schema(
            [
                ":a" => ["slotType:s?", "timeSlot:dt?", "source:s?", "userID:i", "points:i?", "name:s|n", "photo:s|n"],
            ],
            "out"
        )->validate($leaders);

        return $leaders;
    }

    /**
     * @param int $id User ID
     * @return Data
     * @throws ValidationException|NotFoundException
     */
    public function get_profileFields(int $id): Data
    {
        $this->userByID($id);
        return new Data($this->getUserProfileFields($id));
    }

    /**
     * @param int $id
     * @param array $body
     * @return Data
     * @throws ValidationException|NotFoundException
     */
    public function patch_profileFields(int $id, array $body): Data
    {
        $this->userByID($id);
        $in = $this->schema($this->profileFieldModel->getUserProfileFieldSchema());
        $in->addValidator("", $this->profileFieldModel->validateEditable($id));

        $body = $in->validate($body);
        $this->profileFieldModel->updateUserProfileFields($id, $body, true);

        return new Data($this->getUserProfileFields($id));
    }

    /**
     * @param int $id
     * @return array
     * @throws ValidationException
     */
    private function getUserProfileFields(int $id): array
    {
        $out = $this->schema($this->profileFieldModel->getUserProfileFieldSchema(), "out");
        $profileFields = $this->profileFieldModel->getUserProfileFields($id);
        return $out->validate($profileFields);
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    private function normalizeInput(array $schemaRecord)
    {
        if (array_key_exists("bypassSpam", $schemaRecord)) {
            $schemaRecord["verified"] = $schemaRecord["bypassSpam"];
        }
        if (array_key_exists("emailConfirmed", $schemaRecord)) {
            $schemaRecord["confirmed"] = $schemaRecord["emailConfirmed"];
        }
        if (array_key_exists("profileFields", $schemaRecord)) {
            $profileFields = $schemaRecord["profileFields"];
            unset($schemaRecord["profileFields"]);
        }

        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        $dbRecord["ProfileFields"] = $profileFields ?? [];
        return $dbRecord;
    }

    /**
     * Process a user photo upload.
     *
     * @param UploadedFile $photo
     * @throws Exception If there was an error encountered when saving the upload.
     * @return string
     */
    private function processPhoto(UploadedFile $photo)
    {
        // Make sure this upload extension is associated with an allowed image type, then grab the extension.
        $this->imageResizer->imageTypeFromExt($photo->getClientFilename());
        $ext = pathinfo(strtolower($photo->getClientFilename()), PATHINFO_EXTENSION);

        $height = $this->configuration->get("Garden.Profile.MaxHeight");
        $width = $this->configuration->get("Garden.Profile.MaxWidth");
        $thumbSize = $this->configuration->get("Garden.Thumbnail.Size");

        // The image is going to be squared off. Go with the larger dimension.
        $size = $height >= $width ? $height : $width;

        $destination = $photo->generatePersistedUploadPath(ProfileController::AVATAR_FOLDER);

        // Resize/crop the photo, then save it. Save by copying so upload can be used again for the thumbnail.
        $this->savePhoto($photo, $size, changeBasename($destination, "p%s"), true);

        // Resize and save the thumbnail.
        $this->savePhoto($photo, $thumbSize, changeBasename($destination, "n%s"), false);

        return $destination;
    }

    /**
     * Save a photo upload.
     *
     * @param UploadedFile $upload An instance of an uploaded file.
     * @param int $size Maximum size, in pixels, for the photo.
     * @param string $destination
     * @param bool $copy Should the upload be saved by copying, instead of moving?
     */
    private function savePhoto(
        UploadedFile $upload,
        int $size,
        string $destination = ProfileController::AVATAR_FOLDER,
        bool $copy = false
    ) {
        $upload->setImageConstraints(["crop" => true, "height" => $size, "width" => $size]);
        $upload->persistUploadToPath($copy, $destination);
    }

    /**
     * Returns a validator that checks if the current user can assign role ids to another user
     *
     * @param int|null $userID
     * @return Closure
     */
    private function createRoleIDValidator(?int $userID = null): Closure
    {
        return function (array $roleIDs, ValidationField $field) use ($userID) {
            if (empty($roleIDs)) {
                $field->addError("A user must have at least one role.", ["status" => 403]);
            }
            $existingUserRoleIDs = isset($userID) ? $this->userModel->getRoleIDs($userID) : [];
            $assignableRoles = $this->roleModel->getAssignable();

            // Get non-assignable roles by excluding existing user role IDs and assignable role IDs
            $nonassignableRoleIDs = array_diff($roleIDs, $existingUserRoleIDs, array_keys($assignableRoles));

            if (empty($nonassignableRoleIDs)) {
                return;
            }

            $nonassignableRoles = array_filter(array_map([RoleModel::class, "roles"], $nonassignableRoleIDs));
            if (empty($nonassignableRoles)) {
                return;
            }

            $roleNames = array_column($nonassignableRoles, "Name");
            $field->addError("You don't have permission to assign these roles: " . implode(", ", $roleNames), [
                "status" => 403,
            ]);
        };
    }

    /**
     * Get a user by its numeric ID.
     *
     * @param int $id The user ID.
     * @throws NotFoundException if the user could not be found.
     * @return array
     */
    public function userByID($id)
    {
        $row = $this->userModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row || $row["Deleted"] > 0) {
            throw new NotFoundException("User");
        }
        return $row;
    }

    /**
     * Get a user schema with minimal edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function userPatchSchema()
    {
        $schema = $this->schema(
            Schema::parse([
                "name?",
                "email?",
                "showEmail?",
                "suggestAnswers:b?",
                "photo?",
                "emailConfirmed?",
                "bypassSpam?",
                "password?",
                "resetPassword:b?" => [
                    "const" => true,
                ],
                "private?",
                "banned:b?",
                "roleID?" => [
                    "type" => "array",
                    "items" => ["type" => "integer"],
                    "description" => "Roles to set on the user.",
                ],
                "profileFields:o?" => $this->profileFieldModel->getUserProfileFieldSchema(true),
            ])
                ->add($this->fullSchema())
                ->addValidator("", SchemaUtils::onlyOneOf(["password", "resetPassword"]))
                ->addValidator("banned", function ($banned, \Garden\Schema\ValidationField $field) {
                    if (isset($banned)) {
                        $canBan = $this->getSession()->checkPermission(
                            ["Garden.Moderation.Manage", "Garden.Users.Edit", "Moderation.Users.Ban"],
                            false
                        );
                        if (!$canBan) {
                            $field->addError("You do not have permission to ban a user", ["code" => 403]);
                        }
                    }
                }),
            "UserPatch"
        );

        return $schema;
    }
    /**
     * @return Schema
     */
    public function userPatchSelfEditSchema(): Schema
    {
        $schema = $this->schema(
            Schema::parse([
                "name?",
                "email?",
                "showEmail?",
                "suggestAnswers:b?",
                "password?",
                "private?",
                "profileFields:o?" => $this->profileFieldModel->getUserProfileFieldSchema(true),
            ]),
            "UserPatchSelfEdit"
        );
        return $schema;
    }

    /**
     * Get a user schema with minimal add fields.
     *
     * @return Schema Returns a schema object.
     */
    public function userPostSchema()
    {
        $email = c("Garden.Registration.NoEmail", false) ? "email:s?" : "email:s";
        $schema = $this->schema(
            Schema::parse([
                "name",
                $email,
                "showEmail?",
                "photo?",
                "password",
                "emailConfirmed" => ["default" => true],
                "sendWelcomeEmail:b" => ["default" => true],
                "bypassSpam" => ["default" => false],
                "roleID?" => [
                    "type" => "array",
                    "items" => ["type" => "integer"],
                    "description" => "Roles to set on the user.",
                ],
                "private?",
                "profileFields:o?" => $this->profileFieldModel->getUserProfileFieldSchema(true),
            ])->add($this->fullSchema()),
            "UserPost"
        );

        return $schema;
    }

    /**
     * Get the full user schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function userSchema($type = "")
    {
        if ($this->userSchema === null) {
            $this->userSchema = $this->schema($this->userModel->readSchema(), "User");
        }
        return $this->schema($this->userSchema, $type);
    }

    /**
     * Get a user schema with private profile fields.
     *
     * @return Schema Returns a schema object.
     */
    public function viewPrivateProfileSchema()
    {
        return $this->schema(
            Schema::parse([
                "userID:i",
                "name:s?",
                "sortName?",
                "photoUrl:s?",
                "profilePhotoUrl:s?",
                "banned:b?",
                "private:b?" => ["default" => false],
            ])->add($this->fullSchema()),
            "ViewProfile"
        );
    }

    /**
     * Get a user schema with public profile fields.
     *
     * @return Schema Returns a schema object.
     */
    public function viewProfileSchema()
    {
        return $this->schema(
            Schema::parse([
                "userID:i",
                "name:s?",
                "sortName?",
                "email:s?",
                "sortEmail:s?",
                "photoUrl:s?",
                "profilePhotoUrl:s?",
                "url:s?",
                "dateInserted?",
                "dateLastActive:dt?",
                "countDiscussions?",
                "countComments?",
                "label:s?",
                "banned:i?",
                "private:b?" => ["default" => false],
                "countVisits:i?",
                "inviteUserID:i?",
                "countPosts:i?",
                "reactionsReceived?" => $this->reactionModel->compoundTypeFragmentSchema(),
            ])->add($this->fullSchema()),
            "ViewProfile"
        );
    }

    /**
     * @param int $id
     * @param $banned
     * @throws Gdn_UserException
     * @throws \Garden\Web\Exception\ForbiddenException
     */
    private function performBan(int $id, $banned): void
    {
        $userPermissions = $this->userModel->getPermissions($id);
        $rankCompare = Gdn::session()
            ->getPermissions()
            ->compareRankTo($userPermissions);
        if ($rankCompare < 0) {
            throw new \Garden\Web\Exception\ForbiddenException(
                t("You are not allowed to ban a user that has higher permissions than you.")
            );
        } elseif ($rankCompare === 0) {
            throw new \Garden\Web\Exception\ForbiddenException(
                t("You are not allowed to ban a user with the same permission level as you.")
            );
        }

        if ($banned) {
            $this->userModel->ban($id, []);
        } else {
            $this->userModel->unBan($id, []);
        }
    }

    /**
     * Validate the user credentials when patching for one self.
     *
     * @param array $user
     * @param array $body
     * @throws ValidationException
     */
    protected function validatePatchSelfEditCredentials(array $user, array $body): void
    {
        $fieldsPermissions = ["name" => "Garden.Username.Edit"];

        // Global permission checks
        if (!$this->getSession()->checkPermission(["Garden.Profiles.Edit", "Garden.Users.Edit"], false)) {
            throw new \Garden\Web\Exception\ForbiddenException();
        }

        // Password check.
        if ($user["HashMethod"] == "Random" || $user["HashMethod"] == "Reset") {
            // The user doesn't have a password.
            return;
        }

        $normalizedUser = $this->userModel->normalizeRow($user);

        // Password confirmation is only required if one of these fields was passed and has changed.
        $fieldsRequiredOnChange = ["name", "email"];
        $fieldsRequiredAlways = ["password"];
        $needsPasswordConfirmation = false;
        foreach ($fieldsRequiredOnChange as $fieldName) {
            if (isset($body[$fieldName]) && $normalizedUser[$fieldName] !== $body[$fieldName]) {
                $needsPasswordConfirmation = true;
            }
        }
        foreach ($fieldsRequiredAlways as $fieldName) {
            if (isset($body[$fieldName])) {
                $needsPasswordConfirmation = true;
            }
        }

        if ($needsPasswordConfirmation) {
            if (!isset($body["passwordConfirmation"])) {
                $validation = new Validation();
                $validation->addError("passwordConfirmation", self::ERROR_SELF_EDIT_PASSWORD_MISSING);
                throw new ValidationException($validation);
            }
            $this->userModel->validateCredentials("", $normalizedUser["userID"], $body["passwordConfirmation"], true);
        }

        // Check every fields for potential special permissions,
        foreach ($body as $field => $fieldValue) {
            if (isset($fieldsPermissions[$field])) {
                // Permission checks
                $this->permission($fieldsPermissions[$field]);
            }
        }
    }

    /**
     * Get all the comments and discussions a user has posted that have received a certain reaction.
     *
     * @param int $userID
     * @param array $query
     * @return Data
     * @throws ValidationException Throws validation exception.
     * @throws \Garden\Web\Exception\HttpException Http exception.
     * @throws NotFoundException Throws an exception if the user or reaction isn't found.
     * @throws \Vanilla\Exception\PermissionException Permission exception.
     */
    public function get_reacted(int $userID, array $query = []): Data
    {
        $this->permission("Garden.Profiles.View");

        // Make the schemas
        $in = Schema::parse([
            "reactionUrlcode:s",
            "expand" => ApiUtils::getExpandDefinition(["all", "insertUser", "updateUser", "reactions"]),
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => $this->reactionModel->getDefaultLimit(),
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(100),
            ],
        ]);

        $out = Schema::parse([
            ":a" => [
                "name:s",
                "body:s",
                "format:s",
                "insertUserID:i",
                "updateUserID:i?",
                "dateUpdated:dt?",
                "recordID:i",
                "recordType:s",
                "url:s",
                "reactions?" => $this->reactionModel->getReactionSummaryFragment(),
                "insertUser?" => $this->getUserFragmentSchema(),
                "updateUser?" => $this->getUserFragmentSchema(),
            ],
        ]);

        $validatedQuery = $in->validate($query);

        // Throws not found exception if no user found.
        $user = $this->userByID($userID);

        // Get the reaction and throw an error if it isn't found.
        $reactionType = ReactionModel::reactionTypes($validatedQuery["reactionUrlcode"]);
        if (!$reactionType) {
            throw new NotFoundException("Reaction");
        }

        $where = [
            "UserID" => $user["UserID"],
            "RecordType" => ["Discussion-Total", "Comment-Total"],
            "TagID" => $reactionType["TagID"],
            "Total >" => 0,
        ];
        [$offset, $limit] = offsetLimit("p{$validatedQuery["page"]}", $validatedQuery["limit"]);
        $pascalData = $this->reactionModel->getRecordsWhere($where, "DateInserted", "desc", $limit, $offset);

        $expand = $validatedQuery["expand"] ?? [];

        // Convert the array keys for api output.
        $data = [];
        foreach ($pascalData as &$datum) {
            $data[] = ApiUtils::convertOutputKeys($datum);
        }

        // Add the user data, if requested.
        ModelUtils::leftJoin($data, ModelUtils::expandedFields(["insertUser", "updateUser"], $expand), [
            $this->userModel,
            "fetchFragments",
        ]);

        // Add the reaction data, if requested.
        if (ModelUtils::expandedFields(["reactions"], $expand)) {
            $attributes = array_column($pascalData, "Attributes", "RecordID");
            array_walk($data, function (&$data) use ($attributes) {
                $withAttributes = ReactionModel::addAttributes($data, $attributes[$data["recordID"]]);
                $summary = ApiUtils::convertOutputKeys($this->reactionModel->getRecordSummary($withAttributes));
                $data["reactions"] = $summary;
            });
        }

        // Render the body in html.
        foreach ($data as &$record) {
            $record["body"] = Gdn::formatService()->renderHTML($record["body"], $record["format"]);
        }
        $data = $out->validate($data);

        // Get the paging info.
        $paging = ApiUtils::morePagerInfo(
            $data,
            "url",
            ["page" => $validatedQuery["page"], "limit" => $validatedQuery["limit"]],
            $in
        );

        return new Data($data, ["paging" => $paging]);
    }

    /**
     * Bulk update Role assignment for a collection of users
     *
     * @param array $body
     * @return Data
     */
    public function patch_bulkRoleAssignment(array $body = []): Data
    {
        $this->permission("Garden.Users.Edit");

        // Make the schemas
        $in = Schema::parse(["userIDs:a", "addRoleIDs:a?", "removeRoleIDs:a?", "addReplacementRoleIDs:a?"]);
        $in->addValidator("addRoleIDs", $this->createRoleIDValidator())
            ->addValidator("removeRoleIDs", $this->createRoleIDValidator())
            ->addValidator("addReplacementRoleIDs", $this->createRoleIDValidator())
            ->requireOneOf(["addRoleIDs", "removeRoleIDs"])
            ->addValidator("", function ($values, $field) {
                if (!empty($values["removeRoleIDs"]) && empty($values["addReplacementRoleIDs"])) {
                    $count = $this->roleModel->getUserRoleCounts($values["removeRoleIDs"], $values["userIDs"], true);
                    if ($count > 0) {
                        $field->addError("invalidRoles", [
                            "messageCode" => "You must choose a replacement role for orphaned users.",
                        ]);
                    }
                }
                return $values;
            });

        $validatedRoleAssignments = $in->validate($body);

        $result = $this->longRunner->runApi(
            new LongRunnerAction(UserModel::class, "usersRolesIterator", [$validatedRoleAssignments])
        );
        return $result;
    }
}
