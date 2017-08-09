<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/users` resource.
 */
class UsersApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var Schema */
    private $idParamSchema;

    /** @var UserModel */
    private $userModel;

    /** @var Schema */
    private $userPostSchema;

    /** @var Schema */
    private $userSchema;

    /**
     * UsersApiController constructor.
     *
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel) {
        $this->userModel = $userModel;
        $this->caseScheme = new CapitalCaseScheme();
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
            'photo:s' => [
                'allowNull' => true,
                'minLength' => 0,
                'description' => 'Photo of the user.'
            ],
            'confirmed:b' => 'Is the user confirmed?',
            'showEmail:b' => 'Is the email address visible to other users?',
            'verified:b' => 'Has the user been verified?',
            'banned:i' => 'Is the user banned?',
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
        $this->prepareRow($row);

        $result = $out->validate($row);
        $this->prepareRow($result);
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
        $out = $this->schema(Schema::parse(['userID', 'name', 'email', 'photo'])->add($this->fullSchema()), 'out');

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
        $rows = $this->userModel->search('', '', '', $limit, $offset)->resultArray();

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Tweak the data in a user row in a standard way.
     *
     * @param array $row
     */
    protected function prepareRow(array &$row) {
        if (array_key_exists('UserID', $row)) {
            $userID = $row['UserID'];
            $roles = $this->userModel->getRoles($userID)->resultArray();
            $row['roles'] = $roles;
        }
        if (array_key_exists('Photo', $row)) {
            $row['Photo'] = userPhotoUrl($row);
        }
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

        $in = $this->userPostSchema('in')->setDescription('Update a user.');
        $out = $this->userSchema('out');

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->userByID($id);
        $userData = $this->caseScheme->convertArrayKeys($body);
        $userData['UserID'] = $id;
        $this->userModel->save($userData);
        $row = $this->userByID($id);

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

        $in = $this->userPostSchema('in', ['password', 'roleID?'])->setDescription('Add a user.');
        $out = $this->schema($this->userSchema(), 'out');

        $body = $in->validate($body);

        $userData = $this->caseScheme->convertArrayKeys($body);
        if (!array_key_exists('RoleID', $userData)) {
            $userData['RoleID'] = RoleModel::getDefaultRoles(RoleModel::TYPE_MEMBER);
        }
        $settings = [
            'NoConfirmEmail' => true,
            'SaveRoles' => true
        ];
        $id = $this->userModel->save($userData, $settings);

        if (!$id) {
            throw new ServerException('Unable to add user.', 500);
        }

        $row = $this->userByID($id);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return new Data($result, 201);
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
    public function put_verify($id, array $body) {
        $this->permission('Garden.Users.Edit');

        $in = $this
            ->schema(['verified:b' => 'Pass true to flag as verified or false for unverified.'], 'in')
            ->setDescription('Verify a user.');
        $out = $this->schema(['verified:b' => 'The current verified value.'], 'out');

        $row = $this->userByID($id);
        $body = $in->validate($body);
        $verify = intval($body['verified']);
        $this->userModel->setField($id, 'Verified', $verify);

        $result = $this->userByID($id);
        return $out->validate($result);
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
     * Get a user schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @param array|null $extra Additional fields to include.
     * @return Schema Returns a schema object.
     */
    public function userPostSchema($type = '', array $extra = []) {
        if ($this->userPostSchema === null) {
            $schema = Schema::parse([
                'roleID:a' => 'Roles to set on the user.'
            ])->add($this->fullSchema(), true);
            $fields = ['name', 'email', 'photo?'];
            $this->userPostSchema = $this->schema(
                Schema::parse(array_merge($fields, $extra))->add($schema),
                'UserPost'
            );
        }
        return $this->schema($this->userPostSchema, $type);
    }

    /**
     * Get the full user schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function userSchema($type = '') {
        if ($this->userSchema === null) {
            $schema = Schema::parse(['userID', 'name', 'email', 'photo', 'confirmed',
                'showEmail', 'verified', 'banned', 'roles?']);
            $schema = $schema->add($this->fullSchema());
            $this->userSchema = $this->schema($schema, 'User');
        }
        return $this->schema($this->userSchema, $type);
    }
}
