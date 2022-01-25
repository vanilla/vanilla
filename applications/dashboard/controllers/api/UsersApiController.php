<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\DateFilterSchema;
use Vanilla\ImageResizer;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\PermissionFragmentSchema;
use Vanilla\Permissions;
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;
use Vanilla\PermissionsTranslationTrait;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DelimitedScheme;
use Vanilla\Menu\CounterModel;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Menu\Counter;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;

/**
 * API Controller for the `/users` resource.
 */
class UsersApiController extends AbstractApiController {

    use PermissionsTranslationTrait;

    const ME_ACTION_CONSTANT = "@@users/GET_ME_DONE";
    const PERMISSIONS_ACTION_CONSTANT = "@@users/GET_PERMISSIONS_DONE";


    /** @var ActivityModel */
    private $activityModel;

    /** @var Gdn_Configuration */
    private $configuration;

    /** @var CounterModel */
    private $counterModel;

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

    /**
     * UsersApiController constructor.
     *
     * @param UserModel $userModel
     * @param Gdn_Configuration $configuration
     * @param CounterModel $counterModel
     * @param ImageResizer $imageResizer
     * @param ActivityModel $activityModel
     * @param \Vanilla\Web\APIExpandMiddleware $expandMiddleware
     */
    public function __construct(
        UserModel $userModel,
        Gdn_Configuration $configuration,
        CounterModel $counterModel,
        ImageResizer $imageResizer,
        ActivityModel $activityModel,
        \Vanilla\Web\APIExpandMiddleware $expandMiddleware
    ) {
        $this->configuration = $configuration;
        $this->counterModel = $counterModel;
        $this->userModel = $userModel;
        $this->imageResizer = $imageResizer;
        $this->nameScheme =  new DelimitedScheme('.', new CamelCaseScheme());
        $this->activityModel = $activityModel;
        $this->expandMiddleware = $expandMiddleware;
    }

    /**
     * Delete a user.
     *
     * @param int $id The ID of the user.
     * @param array $body The request body.
     * @throws NotFoundException if the user could not be found.
     */
    public function delete($id, array $body) {
        $this->permission('Garden.Users.Delete');

        $this->idParamSchema()->setDescription('Delete a user.');

        $in = $this->schema([
            'deleteMethod:s?' => [
                'description' => 'The deletion method / strategy.',
                'enum' => ['keep', 'wipe', 'delete'],
                'default' => 'delete',
            ]
        ], 'in');
        $out = $this->schema([], 'out');
        $body = $in->validate($body);

        $this->userByID($id);
        $this->userModel->deleteID($id, ['DeleteMethod' => $body['deleteMethod']]);
    }

    /**
     * Delete a user photo.
     *
     * @param int|null $id The ID of the user.
     * @throws ClientException if the user does not have a photo.
     */
    public function delete_photo($id = null) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamSchema()->setDescription('Delete a user photo.');
        $out = $this->schema([], 'out');

        if ($id === null) {
            $id = $this->getSession()->UserID;
        }

        $user = $this->userByID($id);

        if ($id !== $this->getSession()->UserID) {
            $this->permission('Garden.Users.Edit');
        }

        if (empty($user['Photo'])) {
            throw new ClientException('The user does not have a photo.');
        }

        $this->userModel->removePicture($id);
    }

    /**
     * Get a schema instance comprised of all available user fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        $result = $this->userModel
            ->schema();
        return $result;
    }

    /**
     * Get the schema for menu item counts.
     *
     * @return Schema Returns a schema.
     */
    public function getMenuCountsSchema() {
        if ($this->menuCountsSchema === null) {
            $this->menuCountsSchema = $this->schema([
                "counts:a?" => new InstanceValidatorSchema(Counter::class),
            ], 'MenuCounts');
        }
        return $this->menuCountsSchema;
    }

    /**
     * Check if current session user has just Profile.View permission
     * or advanced permissions as well
     *
     * @param string|null $permissionToCheck The permissions you are requiring.
     *
     * @return bool
     */
    public function checkPermission(string $permissionToCheck = null): bool {
        $session = $this->getSession();

        $showFullSchema = false;
        $permissions = [
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete',
            'Garden.PersonalInfo.View'];
        if ($permissionToCheck !== null) {
            $permissions[] = $permissionToCheck;
        }
        if ($session->checkPermission($permissions, false)) {
            $showFullSchema = true;
        } else {
            $permissions = ['Garden.Profiles.View'];
            if ($permissionToCheck !== null) {
                $permissions[] = $permissionToCheck;
            }

            $this->permission($permissions);
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
    public function get(int $id, array $query): Data {
        $showFullSchema = $this->checkPermission(Permissions::BAN_ROLE_TOKEN);

        $queryIn = $this->schema([
            'expand?' => ApiUtils::getExpandDefinition([]),
        ], ['UserGet', 'in'])->setDescription('Get a user.');

        $this->idParamSchema();
        $query = $queryIn->validate($query);
        $expand = $query['expand'] ?? [];
        $outSchema = $showFullSchema ? $this->userSchema() : $this->viewProfileSchema();
        $row = $this->userByID($id);
        $outSchema = CrawlableRecordSchema::applyExpandedSchema($outSchema, 'user', $expand);
        $out =  $this->schema($outSchema, 'out');
        $row = $this->normalizeOutput($row, $expand);

        $showEmail = $row['showEmail'] ?? false;
        if (!$showEmail && !$showFullSchema) {
            unset($row['email']);
        }

        $this->userModel->filterPrivateUserRecord($row);
        $result = $out->validate($row);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('usersApiController_getOutput', $result, $this, $queryIn, $query, $row);

        return new Data($result, ['api-allow' => ['email']]);
    }

    /**
     * Get a user for editing.
     *
     * @param int $id The ID of the user.
     * @throws NotFoundException if the user could not be found.
     * @return Data
     */
    public function get_edit($id) {
        $this->permission('Garden.Users.Edit');

        $in = $this->idParamSchema()->setDescription('Get a user for editing.');
        $out = $this->schema(Schema::parse(['userID', 'name', 'email', 'photo', 'emailConfirmed', 'bypassSpam'])->add($this->fullSchema()), 'out');

        $row = $this->userByID($id);

        $result = $out->validate($row);
        $result = new Data($result, ['api-allow' => ['email']]);
        return $result;
    }

    /**
     * Get global permissions available to the current user.
     *
     * @return array
     */
    private function globalPermissions(): array {
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
    public function getGuestFragment() {
        if ($this->guestFragment === null) {
            $this->guestFragment = [
                "userID" => 0,
                "name" => t("Guest"),
                "photoUrl" => UserModel::getDefaultAvatarUrl(),
                "dateLastActive" => null,
                "isAdmin" => false,
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
    public function index_byNames(array $query) {
        $this->permission();

        $in = $this->schema([
            'name:s' => 'Filter for username. Supports full or partial matching with appended wildcard (e.g. User*).',
            'order:s?' => [
                'description' => 'Sort method for results.',
                'enum' => ['countComments', 'dateLastActive', 'name', 'mention'],
                'default' => 'name'
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => 30,
                'minimum' => 1,
                'maximum' => ApiUtils::getMaxLimit(),
            ]
        ], 'in')->setDescription('Search for users by full or partial name matching.');
        $out = $this->schema([
            ':a' => $this->getUserFragmentSchema(),
        ], 'out');

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        if ($query['order'] == 'mention') {
            [$sortField, $sortDirection] = $this->userModel->getMentionsSort();
        } else {
            $sortField = $query['order'];
            switch ($sortField) {
                case 'countComments':
                case 'dateLastActive':
                    $sortDirection = 'desc';
                    break;
                case 'name':
                default:
                    $sortDirection = 'asc';
            }
        }

        $rows = $this->userModel
            ->searchByName($query['name'], $sortField, $sortDirection, $limit, $offset)
            ->resultArray();

        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $this->userModel->filterPrivateUserRecord($rows);
        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, '/api/v2/users/names', $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get a user's permissions.
     *
     * @param int $id The user's ID.
     * @param array $query Query parameters.
     *
     * @return Data
     */
    public function get_permissions(int $id, array $query = []): Data {
        $requestedUserID = $id;
        $this->permission();
        $in = Schema::parse([
            'expand?' => ApiUtils::getExpandDefinition(['junctions'])
        ]);
        $out = $this->schema([
            "isAdmin:b",
            "isSysAdmin:b",
            'permissions:a' => new PermissionFragmentSchema(),
            'junctions?',
            'junctionAliases?',
        ]);

        $query = $in->validate($query);

        if (is_object($this->getSession()->User)) {
            $sessionUser = (array)$this->getSession()->User;
            $sessionUser = $this->normalizeOutput($sessionUser);
        } else {
            $sessionUser = $this->getGuestFragment();
        }

        // If it's not our own user, then we need to check for a stronger permissions.

        if ($sessionUser['userID'] !== $requestedUserID) {
            $this->permission([
                'Garden.Users.Add',
                'Garden.Users.Edit',
                'Garden.Users.Delete'
            ]);
        }

        // Build the permissions
        // This endpoint is heavily used (every page request), so we rely on caching in the model.
        $permissions = $this->userModel->getPermissions($requestedUserID);

        $result = $permissions->asApiOutput(ModelUtils::isExpandOption('junctions', $query['expand'] ?? []));
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
    public function get_me(array $query) {
        $this->permission();

        $in = $this->schema([], "in")->setDescription("Get information about the current user.");
        $out = $this->schema(Schema::parse([
            "userID",
            "name",
            "photoUrl",
            "email:s|n" => ['default' => null],
            "dateLastActive",
            "isAdmin:b",
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
        ])->add($this->getUserFragmentSchema()), "out");

        $query = $in->validate($query);

        if (is_object($this->getSession()->User)) {
            $user = (array)$this->getSession()->User;
            $user = $this->normalizeOutput($user);
        } else {
            $user = $this->getGuestFragment();
        }

        // Expand permissions for the current user.
        $user["permissions"] = $this->globalPermissions();
        $user["countUnreadNotifications"] = $this->activityModel->getUserTotalUnread($this->getSession()->UserID);
        $user["countUnreadConversations"] = $user['countUnreadConversations'] ?? 0;
        $result = $out->validate($user);

        $response = $this->expandMiddleware->updateResponseByKey($result, 'userID', true);
        $response->setMeta(\Vanilla\Web\ApiFilterMiddleware::FIELD_ALLOW, ['email']);
        $response->setHeader(self::HEADER_CACHE_CONTROL, self::NO_CACHE);

        return $response;
    }

    /**
     * Get all menu counts for current user.
     *
     * @return array
     */
    public function get_meCounts(): array {
        $this->permission();

        $in = $this->schema([], "in");
        $out = $this->schema($this->getMenuCountsSchema(), "out");

        $counters = $this->counterModel->getAllCounters();

        $result = $out->validate([ 'counts' => $counters]);
        return $result;
    }

    /**
     * Get an ID-only user record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The user ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List users.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query) {
        $showFullSchema = $this->checkPermission();

        $in = $this->schema([
            'dateInserted?' => new DateFilterSchema([
                'description' => 'When the user was created.',
                'x-filter' => [
                    'field' => 'u.DateInserted',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'dateUpdated?' => new DateFilterSchema([
                'description' => 'When the user was updated.',
                'x-filter' => [
                    'field' => 'u.DateUpdated',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'dateLastActive?' => new DateFilterSchema([
                'x-filter' => [
                    'field' => 'u.DateLastActive',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'roleID:i?' => [
                'x-filter' => ['field' => 'roleID']
            ],
            'userID?' => \Vanilla\Schema\RangeExpression::createSchema([':int'])->setField('x-filter', ['field' => 'u.UserID']),
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'dirtyRecords:b?',
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => 30,
                'minimum' => 1,
                'maximum' => ApiUtils::getMaxLimit(),
            ],
            'sort:s?' => [
                'enum' => ApiUtils::sortEnum('dateInserted', 'dateLastActive', 'name', 'userID')
            ],
            'expand?' => ApiUtils::getExpandDefinition([]),
        ], ['UserIndex', 'in'])
            ->addValidator("", SchemaUtils::onlyOneOf(["dateInserted", "dateUpdated", "roleID", "userID"]));

        $query = $in->validate($query);

        $expand = $query['expand'] ?? [];
        $outSchema = $showFullSchema ? $this->userSchema() : $this->viewProfileSchema();
        $outSchema = CrawlableRecordSchema::applyExpandedSchema($outSchema, 'user', $expand);
        $out = $this->schema([':a' => $outSchema], 'out');

        $where = ApiUtils::queryToFilters($in, $query);

        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $joinDirtyRecords = $query[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($joinDirtyRecords) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = $query[DirtyRecordModel::DIRTY_RECORD_OPT];
        }

        $rows = $this->userModel->search($where, $query['sort'] ?? '', '', $limit, $offset)->resultArray();

        // Join in the roles more efficiently for the index.
        // Attempting to join roles from cache works well for single records where a user might be coming back over and over,
        // But isn't really appropriate for iterating over lists of users where the same user will not likely be seen twice.
        // Fetch all roles at once.
        $this->userModel->joinRoles($rows);

        foreach ($rows as &$row) {
            $this->userModel->setCalculatedFields($row);
            $row = $this->normalizeOutput($row, $expand);
            $showEmail = $row['showEmail'] ?? false;
            if (!$showEmail && !$showFullSchema) {
                unset($row['email']);
            }
        }

        $this->userModel->filterPrivateUserRecord($rows);
        $result = $out->validate($rows);

        // Determine if we are gonna use the "numbered" or "more" pageInfo.
        if (empty($where)) {
            if (!Gdn::userModel()->pastUserMegaThreshold()) {
                $totalCount = $this->userModel->getCount();
            }
        } elseif (!Gdn::userModel()->pastUserThreshold()) {
            if ($joinDirtyRecords) {
                $this->userModel->applyDirtyWheres('u');
                unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
            }
            $totalCount = $this->userModel->searchCount($where);
        }

        if (isset($totalCount)) {
            $paging = ApiUtils::numberedPagerInfo($totalCount, '/api/v2/users', $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($result, '/api/v2/users', $query, $in);
        }

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('usersApiController_indexOutput', $result, $this, $in, $query, $rows);

        return new Data($result, ['paging' => $paging, 'api-allow' => ['email']]);
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|string|bool $expand
     *
     * @return array Return a Schema record.
     */
    protected function normalizeOutput(array $dbRecord, $expand = []) {
        $result = $this->userModel->normalizeRow($dbRecord, $expand);
        $result['url'] = $this->userModel->getProfileUrl($result);
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
    public function patch($id, array $body) {
        $this->permission('Garden.Users.Edit');

        $this->idParamSchema('in');
        $in = $this->schema($this->userPatchSchema(), 'in')->setDescription('Update a user.');
        $out = $this->userSchema('out');

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->userByID($id);
        $userData = $this->normalizeInput($body);
        $userData['UserID'] = $id;
        $settings = ['ValidateName' => false];
        if (!empty($userData['RoleID'])) {
            $settings['SaveRoles'] = true;
        }
        $this->userModel->save($userData, $settings);
        $this->validateModel($this->userModel);
        $row = $this->userByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        $result = new Data($result, ['api-allow' => ['email']]);
        return $result;
    }

    /**
     * Add a user.
     *
     * @param array $body The request body.
     * @throws ServerException if the user could not be added.
     * @return Data
     */
    public function post(array $body) {
        $this->permission('Garden.Users.Add');

        $in = $this->schema($this->userPostSchema(), 'in')->setDescription('Add a user.');
        $out = $this->schema($this->userSchema(), 'out');

        $body = $in->validate($body);

        $userData = $this->normalizeInput($body);
        $settings = [
            'NoConfirmEmail' => true,
            'SaveRoles' => array_key_exists('RoleID', $userData),
            'ValidateName' => false
        ];
        $id = $this->userModel->save($userData, $settings);
        $this->validateModel($this->userModel);

        if (!$id) {
            throw new ServerException('Unable to add user.', 500);
        }

        $row = $this->userByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        $result = new Data($result, ['api-allow' => ['email']]);
        return $result;
    }

    /**
     * Set a new photo on a user.
     *
     * @param ?int $id A valid user ID.
     * @param array $body The request body.
     * @param \Garden\Web\RequestInterface|null $request
     * @return array
     */
    public function post_photo($id = null, array $body = [], \Garden\Web\RequestInterface $request = null) {
        $this->permission('Garden.SignIn.Allow');

        $photoUploadSchema = new UploadedFileSchema([
            'allowedExtensions' => ImageResizer::getAllExtensions()
        ]);

        $in = $this->schema([
            'photo' => $photoUploadSchema
        ], 'in');
        $out = $this->schema(Schema::parse(['photoUrl'])->add($this->fullSchema()), 'out');

        if ($id === null) {
            $id = $this->getSession()->UserID;
        }

        $this->userByID($id);

        if ($id !== $this->getSession()->UserID) {
            $this->permission('Garden.Users.Edit');
        }
        if ($request !== null) {
            UploadedFileSchema::validateUploadSanity($body, 'photo', $request);
        }
        $body = $in->validate($body);

        $photo = $this->processPhoto($body['photo']);
        $this->userModel->removePicture($id);
        $this->userModel->save(['UserID' => $id, 'Photo' => $photo]);

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
    public function post_register(array $body) {
        $this->permission([\Vanilla\Permissions::BAN_CSRF, \Vanilla\Permissions::BAN_PRIVATE]);

        $registrationMethod = $this->configuration->get('Garden.Registration.Method');
        $registrationMethod = strtolower($registrationMethod);

        $userData = ApiUtils::convertInputKeys($body);

        $email = c('Garden.Registration.NoEmail', false) ? 'email:s?' : 'email:s';

        $inputProperties = [
            $email => 'An email address for this user.',
            'name:s' => 'The username.',
            'password:s' => 'A password for this user.',
            'discoveryText:s?' => 'Why does the user wish to join? Only used when the registration is flagged as SPAM (response code: 202).'
        ];
        if ($registrationMethod === 'invitation') {
            $inputProperties['invitationCode:s'] = 'An invitation code for registering on the site.';
        } elseif ($this->userModel->isRegistrationSpam($userData)) {
            // SPAM detected. Require a reason to join.
            $inputProperties['discoveryText:s'] = $inputProperties['discoveryText:s?'];
            unset($inputProperties['discoveryText:s?']);
        }

        $in = $this->schema($inputProperties, 'in')->setDescription('Submit a new user registration.');
        $out = $this->schema(
            Schema::parse(['userID', 'name', 'email'])->add($this->fullSchema()),
            'out'
        );

        $in->validate($body);

        $this->userModel->validatePasswordStrength($userData['Password'], $userData['Name']);

        switch ($registrationMethod) {
            case 'invitation':
                $userID = $this->userModel->insertForInvite($userData);
                break;
            case 'basic':
            case 'captcha':
                $userID = $this->userModel->insertForBasic($userData);
                break;
            default:
                throw new ClientException('Unsupported registration method.');
        }
        $this->validateModel($this->userModel);

        if (!$userID) {
            throw new ServerException('An unknown error occurred while attempting to create the user.', 500);
        } elseif ($userID === UserModel::REDIRECT_APPROVE) {
            // A registration has been flagged for approval. Indicate the request has been accepted, but a user hasn't necessarily been created.
            $result = new Data([], 202);
        } else {
            $row = $this->userByID($userID);
            $result = $out->validate($row);
            $result = new Data($result, 201);
        }
        $result = new Data($result, ['api-allow' => ['email']]);
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
    public function put_ban($id, array $body) {
        $this->permission(['Garden.Moderation.Manage', 'Garden.Users.Edit', 'Moderation.Users.Ban']);

        $this->idParamSchema('in');
        $in = $this
            ->schema(['banned:b' => 'Pass true to ban or false to unban.'], 'in')
            ->setDescription('Ban a user.');
        $out = $this->schema(['banned:b' => 'The current banned value.'], 'out');

        $row = $this->userByID($id);
        $body = $in->validate($body);

        // Check ranking permissions.
        $userPermissions = $this->userModel->getPermissions($id);
        $rankCompare = Gdn::session()->getPermissions()->compareRankTo($userPermissions);
        if ($rankCompare < 0) {
            throw new \Garden\Web\Exception\ForbiddenException(t('You are not allowed to ban a user that has higher permissions than you.'));
        } elseif ($rankCompare === 0) {
            throw new \Garden\Web\Exception\ForbiddenException(t('You are not allowed to ban a user with the same permission level as you.'));
        }

        if ($body['banned']) {
            $this->userModel->ban($id, []);
        } else {
            $this->userModel->unBan($id, []);
        }

        $result = $this->userByID($id);
        return $out->validate($result);
    }

    /**
     * Send a password reset email.
     *
     * @param array $body The POST body.
     * @throws Exception Throws all exceptions to the dispatcher.
     */
    public function post_requestPassword(array $body) {
        $this->permission(\Vanilla\Permissions::BAN_PRIVATE);

        $in = $this->schema([
            'email:s' => 'The email/username of the user.',
        ]);
        $out = $this->schema([], 'out');

        $body = $in->validate($body);

        $this->userModel->passwordRequest($body['email']);
        $this->validateModel($this->userModel, true);
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
    public function post_confirmEmail($id, array $body) {
        $this->permission(\Vanilla\Permissions::BAN_CSRF);

        $this->idParamSchema('in');
        $in = $this->schema([
            'confirmationCode:s' => 'Email confirmation code'
        ], 'in')->setDescription('Confirm a users current email address by using a confirmation code');
        $out = $this->schema(['userID:i', 'email:s', 'emailConfirmed:b'], 'out');

        $row = $this->userByID($id);
        if ($row['Confirmed']) {
            throw new ClientException('This email has already been confirmed');
        }

        $body = $in->validate($body);
        $this->userModel->confirmEmail($row, $body['confirmationCode']);
        $this->validateModel($this->userModel);

        $result = $out->validate($this->userByID($id));
        $result = new Data($result, ['api-allow' => ['email']]);
        return $result;
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    private function normalizeInput(array $schemaRecord) {
        if (array_key_exists('bypassSpam', $schemaRecord)) {
            $schemaRecord['verified'] = $schemaRecord['bypassSpam'];
        }
        if (array_key_exists('emailConfirmed', $schemaRecord)) {
            $schemaRecord['confirmed'] = $schemaRecord['emailConfirmed'];
        }

        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Process a user photo upload.
     *
     * @param UploadedFile $photo
     * @throws Exception If there was an error encountered when saving the upload.
     * @return string
     */
    private function processPhoto(UploadedFile $photo) {
        // Make sure this upload extension is associated with an allowed image type, then grab the extension.
        $this->imageResizer->imageTypeFromExt($photo->getClientFilename());
        $ext = pathinfo(strtolower($photo->getClientFilename()), PATHINFO_EXTENSION);

        $height = $this->configuration->get('Garden.Profile.MaxHeight');
        $width = $this->configuration->get('Garden.Profile.MaxWidth');
        $thumbSize = $this->configuration->get('Garden.Thumbnail.Size');

        // The image is going to be squared off. Go with the larger dimension.
        $size = $height >= $width ? $height : $width;

        $destination = $photo->generatePersistedUploadPath(ProfileController::AVATAR_FOLDER);

        // Resize/crop the photo, then save it. Save by copying so upload can be used again for the thumbnail.
        $this->savePhoto($photo, $size, changeBasename($destination, 'p%s'), true);

        // Resize and save the thumbnail.
        $this->savePhoto($photo, $thumbSize, changeBasename($destination, 'n%s'), false);

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
    private function savePhoto(UploadedFile $upload, int $size, string $destination = ProfileController::AVATAR_FOLDER, bool $copy = false) {
        $upload->setImageConstraints(['crop' => true, 'height' => $size, 'width' => $size]);
        $upload->persistUploadToPath($copy, $destination);
    }

    /**
     * Get a user by its numeric ID.
     *
     * @param int $id The user ID.
     * @throws NotFoundException if the user could not be found.
     * @return array
     */
    public function userByID($id) {
        $row = $this->userModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row || $row['Deleted'] > 0) {
            throw new NotFoundException('User');
        }
        return $row;
    }

    /**
     * Get a user schema with minimal edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function userPatchSchema() {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema(Schema::parse([
                'name?', 'email?', 'photo?', 'emailConfirmed?', 'bypassSpam?', 'password?',
                'roleID?' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Roles to set on the user.'
                ]
            ])->add($this->fullSchema()), 'UserPatch');
        }

        return $schema;
    }

    /**
     * Get a user schema with minimal add fields.
     *
     * @return Schema Returns a schema object.
     */
    public function userPostSchema() {
        static $schema;
        $email = c('Garden.Registration.NoEmail', false) ? 'email:s?' : 'email:s';
        if ($schema === null) {
            $schema = $this->schema(Schema::parse([
                'name', $email, 'photo?', 'password',
                'emailConfirmed' => ['default' => true], 'bypassSpam' => ['default' => false],
                'roleID?' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Roles to set on the user.'
                ]
            ])->add($this->fullSchema()), 'UserPost');
        }

        return $schema;
    }

    /**
     * Get the full user schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function userSchema($type = '') {
        if ($this->userSchema === null) {
            $this->userSchema = $this->schema($this->userModel->readSchema(), 'User');
        }
        return $this->schema($this->userSchema, $type);
    }

    /**
     * Get a user schema with minimal profile fields.
     *
     * @return Schema Returns a schema object.
     */
    public function viewProfileSchema() {
        return $this->schema(Schema::parse([
                'userID:i',
                'name:s?',
                'sortName?',
                'email:s?',
                'photoUrl:s?',
                'profilePhotoUrl:s?',
                'url:s?',
                'dateInserted?',
                'dateLastActive:dt?',
                'countDiscussions?',
                'countComments?',
                'label:s?',
                'banned:i?',
                'private:b?' => ['default' => false]
        ])->add($this->fullSchema()), 'ViewProfile');
    }
}
