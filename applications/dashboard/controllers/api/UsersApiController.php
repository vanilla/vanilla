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
use Vanilla\UploadedFile;
use Vanilla\UploadedFileSchema;
use Vanilla\PermissionsTranslationTrait;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DelimitedScheme;
use Vanilla\Menu\CounterModel;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Menu\Counter;

/**
 * API Controller for the `/users` resource.
 */
class UsersApiController extends AbstractApiController {

    use PermissionsTranslationTrait;

    const ME_ACTION_CONSTANT = "@@users/GET_ME_DONE";

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
     * UsersApiController constructor.
     *
     * @param UserModel $userModel
     * @param Gdn_Configuration $configuration
     * @param ImageResizer $imageResizer
     */
    public function __construct(
        UserModel $userModel,
        Gdn_Configuration $configuration,
        CounterModel $counterModel,
        ImageResizer $imageResizer,
        ActivityModel $activityModel
    ) {
        $this->configuration = $configuration;
        $this->counterModel = $counterModel;
        $this->userModel = $userModel;
        $this->imageResizer = $imageResizer;
        $this->nameScheme =  new DelimitedScheme('.', new CamelCaseScheme());
        $this->activityModel = $activityModel;
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
        $schema = Schema::parse([
            'userID:i' => 'ID of the user.',
            'name:s' => 'Name of the user.',
            'password:s' => 'Password of the user.',
            'hashMethod:s' => 'Hash method for the password.',
            'email:s' => [
                'description' => 'Email address of the user.',
                'minLength' => 0,
            ],
            'photo:s|n' => [
                'minLength' => 0,
                'description' => 'Raw photo field value from the user record.'
            ],
            'photoUrl:s|n' => [
                'minLength' => 0,
                'description' => 'URL to the user photo.'
            ],
            'points:i',
            'emailConfirmed:b' => 'Has the email address for this user been confirmed?',
            'showEmail:b' => 'Is the email address visible to other users?',
            'bypassSpam:b' => 'Should submissions from this user bypass SPAM checks?',
            'banned:i' => 'Is the user banned?',
            'dateInserted:dt' => 'When the user was created.',
            'dateLastActive:dt|n' => 'Time the user was last active.',
            'dateUpdated:dt|n' => 'When the user was last updated.',
            'roles:a?' => $this->schema([
                'roleID:i' => 'ID of the role.',
                'name:s' => 'Name of the role.'
            ], 'RoleFragment'),
        ]);
        return $schema;
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
     * Get a single user.
     *
     * @param int $id The ID of the user.
     * @param array $query The request query.
     * @throws NotFoundException if the user could not be found.
     * @return array
     */
    public function get($id, array $query) {
        $this->permission([
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete'
        ]);

        $this->idParamSchema();
        $in = $this->schema([], ['UserGet', 'in'])->setDescription('Get a user.');
        $out = $this->schema($this->userSchema(), 'out');

        $query = $in->validate($query);
        $row = $this->userByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('usersApiController_getOutput', $result, $this, $in, $query, $row);
        return $result;
    }

    /**
     * Get a user for editing.
     *
     * @param int $id The ID of the user.
     * @throws NotFoundException if the user could not be found.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.Users.Edit');

        $in = $this->idParamSchema()->setDescription('Get a user for editing.');
        $out = $this->schema(Schema::parse(['userID', 'name', 'email', 'photo', 'emailConfirmed', 'bypassSpam'])->add($this->fullSchema()), 'out');

        $row = $this->userByID($id);

        $result = $out->validate($row);
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
                'maximum' => 100,
            ]
        ], 'in')->setDescription('Search for users by full or partial name matching.');
        $out = $this->schema([
            ':a' => $this->getUserFragmentSchema(),
        ], 'out');

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        if ($query['order'] == 'mention') {
            list($sortField, $sortDirection) = $this->userModel->getMentionsSort();
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
        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, '/api/v2/users/names', $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get a fragment representing the current user.
     *
     * @param array $query
     * @return array
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
            "dateLastActive",
            "isAdmin:b",
            "countUnreadNotifications" => [
                "description" => "Total number of unread notifications for the current user.",
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

        $result = $out->validate($user);
        return $result;
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
        $this->permission([
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete'
        ]);

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
            'userID:a?' => [
                'description' => 'One or more user IDs to lookup.',
                'items' => ['type' => 'integer'],
                'style' => 'form',
                'x-filter' => [
                    'field' => 'u.UserID',
                ],
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
                'maximum' => 100,
            ]
        ], ['UserIndex', 'in'])->setDescription('List users.');
        $out = $this->schema([':a' => $this->userSchema()], 'out');

        $query = $in->validate($query);
        $where = ApiUtils::queryToFilters($in, $query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $rows = $this->userModel->search($where, '', '', $limit, $offset)->resultArray();
        foreach ($rows as &$row) {
            $this->userModel->setCalculatedFields($row);
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($rows);

        // Determine if we are gonna use the "numbered" or "more" pageInfo.
        if (empty($where)) {
            if (!Gdn::userModel()->pastUserMegaThreshold()) {
                $totalCount = $this->userModel->getCount();
            }
        } elseif (!Gdn::userModel()->pastUserThreshold()) {
            $totalCount = $this->userModel->searchCount($where);
        }

        if (isset($totalCount)) {
            $paging = ApiUtils::numberedPagerInfo($totalCount, '/api/v2/users', $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($result, '/api/v2/users', $query, $in);
        }

        // Allow addons to modify the result.
        $result = $this->getEventManager()->fireFilter('usersApiController_indexOutput', $result, $this, $in, $query, $rows);
        return new Data($result, ['paging' => $paging]);

    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    protected function normalizeOutput(array $dbRecord) {
        if (array_key_exists('UserID', $dbRecord)) {
            $userID = $dbRecord['UserID'];
            $roles = $this->userModel->getRoles($userID)->resultArray();
            $dbRecord['roles'] = $roles;
        }
        if (array_key_exists('Photo', $dbRecord)) {
            $photo = userPhotoUrl($dbRecord);
            $dbRecord['Photo'] = $photo;
            $dbRecord['PhotoUrl'] = $photo;
        }
        if (array_key_exists('Verified', $dbRecord)) {
            $dbRecord['bypassSpam'] = $dbRecord['Verified'];
            unset($dbRecord['Verified']);
        }
        if (array_key_exists('Confirmed', $dbRecord)) {
            $dbRecord['emailConfirmed'] = $dbRecord['Confirmed'];
            unset($dbRecord['Confirmed']);
        }
        if (array_key_exists('Admin', $dbRecord)) {
            // The site creator is 1, System is 2.
            $dbRecord['isAdmin'] = in_array($dbRecord['Admin'], [1, 2]);
            unset($dbRecord['Admin']);
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Update a user.
     *
     * @param int $id The ID of the user.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the user.
     * @return array
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
        return new Data($result, 201);
    }

    /**
     * Set a new photo on a user.
     *
     * @param $id A valid user ID.
     * @param array $body The request body.
     * @throws ClientException if the image provided is not supported.
     * @return array
     */
    public function post_photo($id = null, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $photoUploadSchema = new UploadedFileSchema([
            'allowedExtensions' => array_values(ImageResizer::getTypeExt())
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

        return $result;
    }

    /**
     * Ban a user.
     *
     * @param int $id The ID of the user.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the user.
     * @return array
     */
    public function put_ban($id, array $body) {
        $this->permission('Garden.Users.Edit');

        $this->idParamSchema('in');
        $in = $this
            ->schema(['banned:b' => 'Pass true to ban or false to unban.'], 'in')
            ->setDescription('Ban a user.');
        $out = $this->schema(['banned:b' => 'The current banned value.'], 'out');

        $row = $this->userByID($id);
        $body = $in->validate($body);
        $banned = intval($body['banned']);
        $this->userModel->setField($id, 'Banned', $banned);

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
     * @return array the response body.
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
     * @throws Exception if there was an error encountered when saving the upload.
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

        $destination = ProfileController::AVATAR_FOLDER.'/'.$this->generateUploadPath($ext, true);

        // Resize/crop the photo, then save it. Save by copying so upload can be used again for the thumbnail.
        $this->savePhoto($photo, $destination, $size, 'p', true);

        // Resize and save the thumbnail.
        $this->savePhoto($photo, $destination, $thumbSize, 'n');

        return $destination;
    }

    /**
     * Save a photo upload.
     *
     * @param UploadedFile $upload An instance of an uploaded file.
     * @param string $destination The path, relative to the uploads directory, to save the images into.
     * @param int $size Maximum size, in pixels, for the photo.
     * @param string $prefix An optional prefix (e.g. p for full-size or n for thumbnail).
     * @param bool $copy Should the upload be saved by copying, instead of moving?
     * @throws Exception if there was an error encountered when saving the upload.
     * @return array|bool
     */
    private function savePhoto(UploadedFile $upload, $destination, $size, $prefix = '', $copy = false) {
        $this->imageResizer->resize(
            $upload->getFile(),
            null,
            ['crop' => true, 'height' => $size, 'width' => $size]
        );

        $result = $this->saveUpload($upload, $destination, "{$prefix}%s", $copy);
        return $result;
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
                'name?', 'email?', 'photo?', 'emailConfirmed?', 'bypassSpam?',
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
            $schema = Schema::parse(['userID', 'name', 'email', 'photoUrl', 'points', 'emailConfirmed',
                'showEmail', 'bypassSpam', 'banned', 'dateInserted', 'dateLastActive', 'dateUpdated', 'roles?']);
            $schema = $schema->add($this->fullSchema());
            $this->userSchema = $this->schema($schema, 'User');
        }
        return $this->schema($this->userSchema, $type);
    }
}
