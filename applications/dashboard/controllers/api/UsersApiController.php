<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use \Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\DateFilterSchema;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/users` resource.
 */
class UsersApiController extends AbstractApiController {

    /** @var Gdn_Configuration */
    private $configuration;

    /** @var DateFilterSchema */
    private $dateFilterSchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /** @var Schema */
    private $userSchema;

    /**
     * UsersApiController constructor.
     *
     * @param UserModel $userModel
     * @param Gdn_Configuration $configuration
     * @param DateFilterSchema $dateFilterSchema
     */
    public function __construct(
        UserModel $userModel,
        Gdn_Configuration $configuration,
        DateFilterSchema $dateFilterSchema
    ) {
        $this->configuration = $configuration;
        $this->userModel = $userModel;
        $this->dateFilterSchema = $dateFilterSchema;
    }

    /**
     * Delete a user.
     *
     * @param int $id The ID of the user.
     * @throws NotFoundException if the user could not be found.
     */
    public function delete($id) {
        $this->permission('Garden.Users.Delete');

        $in = $this->idParamSchema()->setDescription('Delete a user.');
        $out = $this->schema([], 'out');

        $this->userByID($id);
        $this->userModel->deleteID($id);
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
            'email:s' => 'Email address of the user.',
            'photo:s|n' => [
                'minLength' => 0,
                'description' => 'Raw photo field value from the user record.'
            ],
            'photoUrl:s|n' => [
                'minLength' => 0,
                'description' => 'URL to the user photo.'
            ],
            'emailConfirmed:b' => 'Has the email address for this user been confirmed?',
            'showEmail:b' => 'Is the email address visible to other users?',
            'bypassSpam:b' => 'Should submissions from this user bypass SPAM checks?',
            'banned:i' => 'Is the user banned?',
            'dateInserted:dt' => 'When the user was created.',
            'dateUpdated:dt|n' => 'When the user was last updated.',
            'roles:a?' => $this->schema([
                'roleID:i' => 'ID of the role.',
                'name:s' => 'Name of the role.'
            ], 'RoleFragment')
        ]);
        return $schema;
    }

    /**
     * Get a single user.
     *
     * @param int $id The ID of the user.
     * @throws NotFoundException if the user could not be found.
     * @return array
     */
    public function get($id) {
        $this->permission([
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete'
        ]);

        $in = $this->idParamSchema()->setDescription('Get a user.');
        $out = $this->schema($this->userSchema(), 'out');

        $row = $this->userByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
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
     * @return array
     */
    public function index(array $query) {
        $this->permission([
            'Garden.Users.Add',
            'Garden.Users.Edit',
            'Garden.Users.Delete'
        ]);

        $in = $this->schema([
            'dateInserted?' => $this->dateFilterSchema,
            'dateUpdated?' => $this->dateFilterSchema,
            'userID:a?' => [
                'description' => 'One or more user IDs to lookup.',
                'items' => ['type' => 'integer'],
                'style' => 'form'
            ],
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => 30,
                'minimum' => 1,
                'maximum' => 100
            ]
        ], 'in')->setDescription('List users.');
        $out = $this->schema([':a' => $this->userSchema()], 'out');

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $filter = [];

        if (!empty($query['userID'])) {
            $filter['UserID'] = $query['userID'];
        }

        if ($dateInserted = $this->dateFilterField('dateInserted', $query)) {
            $filter += $dateInserted;
        }
        if ($dateUpdated = $this->dateFilterField('dateUpdated', $query)) {
            $filter += $dateUpdated;
        }

        $rows = $this->userModel->search($filter, '', '', $limit, $offset)->resultArray();
        foreach ($rows as &$row) {
            $this->userModel->setCalculatedFields($row);
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($rows);
        return $result;
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
        $settings = [];
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
        if (!array_key_exists('RoleID', $userData)) {
            $userData['RoleID'] = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        }
        $settings = [
            'NoConfirmEmail' => true,
            'SaveRoles' => true
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
     * Submit a new user registration.
     *
     * @param array $body The request body.
     * @throws ClientException if terms of service field is false.
     * @throws ServerException if an unknown error was encountered when creating the user.
     * @throws ValidationException if the registration is flagged as SPAM, but no discoveryText was provided.
     * @return Data
     */
    public function post_register(array $body) {
        $this->permission(\Vanilla\Permissions::BAN_CSRF);

        $registrationMethod = $this->configuration->get('Garden.Registration.Method');
        $registrationMethod = strtolower($registrationMethod);

        $userData = ApiUtils::convertInputKeys($body);

        $inputProperties = [
            'email:s' => 'An email address for this user.',
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
        $out = $this->schema(['userID', 'name', 'email'], 'out')->add($this->fullSchema());

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
     * Verify a user.
     *
     * @param int $id The ID of the user.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the user.
     * @return array
     */
//    public function put_verify($id, array $body) {
//        $this->permission('Garden.Users.Edit');
//
//        $in = $this
//            ->schema(['verified:b' => 'Pass true to flag as verified or false for unverified.'], 'in')
//            ->setDescription('Verify a user.');
//        $out = $this->schema(['verified:b' => 'The current verified value.'], 'out');
//
//        $row = $this->userByID($id);
//        $body = $in->validate($body);
//        $verify = intval($body['verified']);
//        $this->userModel->setField($id, 'Verified', $verify);
//
//        $result = $this->userByID($id);
//        return $out->validate($result);
//    }

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

        if ($schema === null) {
            $schema = $this->schema(Schema::parse([
                'name', 'email', 'photo?', 'password',
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
            $schema = Schema::parse(['userID', 'name', 'email', 'photoUrl', 'emailConfirmed',
                'showEmail', 'bypassSpam', 'banned', 'dateInserted', 'dateUpdated', 'roles?']);
            $schema = $schema->add($this->fullSchema());
            $this->userSchema = $this->schema($schema, 'User');
        }
        return $this->schema($this->userSchema, $type);
    }
}
