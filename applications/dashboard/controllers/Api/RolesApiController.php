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
 * API Controller for the `/roles` resource.
 */
class RolesApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var Schema */
    private $idParamSchema;

    /** @var PermissionModel */
    private $permissionModel;

    /** @var RoleModel */
    private $roleModel;

    /** @var Schema */
    private $rolePostSchema;

    /** @var Schema */
    private $roleSchema;

    /**
     * RolesApiController constructor.
     *
     * @param RoleModel $roleModel
     */
    public function __construct(RoleModel $roleModel, PermissionModel $permissionModel) {
        $this->roleModel = $roleModel;
        $this->permissionModel = $permissionModel;
        $this->caseScheme = new CapitalCaseScheme();
    }

    /**
     * Delete a role.
     *
     * @param int $id The ID of the role.
     */
    public function delete($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema()->setDescription('Delete a role.');
        $out = $this->schema([], 'out');

        $this->roleByID($id);
        $this->roleModel->deleteID($id);
    }

    /**
     * Get a schema instance comprised of all available role fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        $schema = Schema::parse([
            'roleID:i' => 'ID of the role.',
            'name:s' => 'Name of the role.',
            'description:s' => [
                'allowNull' => true,
                'description' => 'Description of the role.',
                'minLength' => 0
            ],
            'type:s' => [
                'allowNull' => true,
                'description' => 'Default type of this role.',
                'minLength' => 0
            ],
            'deletable:b' => 'Is the role deletable?',
            'canSession:b' => 'Can users in this role start a session?',
            'personalInfo:b' => 'Is membership in this role personal information?',
            'permissions:o?' => 'Permissions available to the role.'
        ]);
        return $schema;
    }

    /**
     * Get a single role.
     *
     * @param int $id The ID of the role.
     * @throws NotFoundException if unable to find the role.
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema()->setDescription('Get a role.');
        $out = $this->schema($this->roleSchema(), 'out');

        $row = $this->roleByID($id);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a role for editing.
     *
     * @param int $id The ID of the role.
     * @throws NotFoundException if unable to find the role.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.Settings.Manage');

        $editFields = ['roleID', 'name', 'description', 'type', 'deletable', 'canSession', 'personalInfo'];
        $in = $this->idParamSchema()->setDescription('Get a role for editing.');
        $out = $this->schema(Schema::parse($editFields)->add($this->fullSchema()), 'out');

        $row = $this->roleByID($id);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only role record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The role ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List roles.
     *
     * @return array
     */
    public function index() {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema([], 'in')->setDescription('List roles.');
        $out = $this->schema([':a' => $this->roleSchema()], 'out');

        $rows = $this->roleModel->getWithRankPermissions()->resultArray();
        foreach ($rows as &$row) {
            $this->prepareRow($row);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Tweak the data in a role row in a standard way.
     *
     * @param array $row
     */
    protected function prepareRow(array &$row) {
        if (array_key_exists('RoleID', $row)) {
            $roleID = $row['RoleID'];
            $rawPerms = $this->permissionModel->getPermissionsByRole($roleID);
            $perms = [];
            foreach ($rawPerms as $permission => $value) {
                if (is_int($permission)) {
                    $permission = $value;
                    $value = true;
                }
                $perms[$permission] = $value;
            }
            ksort($perms);
            $row['permissions'] = $perms;
        }
    }

    /**
     * Update a role.
     *
     * @param int $id The ID of the role.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the role.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->rolePostSchema('in')->setDescription('Update a role.');
        $out = $this->roleSchema('out');

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->roleByID($id);
        $roleData = $this->caseScheme->convertArrayKeys($body);
        $roleData['RoleID'] = $id;
        $this->roleModel->save($roleData);
        $row = $this->roleByID($id);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Add a role.
     *
     * @param array $body The request body.
     * @throws ServerException if the role could not be added.
     * @return Data
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->rolePostSchema()->setDescription('Add a role.');
        $out = $this->schema($this->roleSchema(), 'out');

        $body = $in->validate($body);

        $roleData = $this->caseScheme->convertArrayKeys($body);
        $id = $this->roleModel->save($roleData);

        if (!$id) {
            throw new ServerException('Unable to add role.', 500);
        }

        $row = $this->roleByID($id);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return new Data($result, 201);
    }

    /**
     * Get a role by its numeric ID.
     *
     * @param int $id The role ID.
     * @throws NotFoundException if the role could not be found.
     * @return array
     */
    public function roleByID($id) {
        $row = $this->roleModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Role');
        }
        return $row;
    }

    /**
     * Get a role schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function rolePostSchema($type = '') {
        if ($this->rolePostSchema === null) {
            $fields = ['name', 'description?', 'type?', 'deletable?', 'canSession?', 'personalInfo?'];
            $this->rolePostSchema = $this->schema(
                Schema::parse($fields)->add($this->fullSchema()),
                'RolePost'
            );
        }
        return $this->schema($this->rolePostSchema, $type);
    }

    /**
     * Get the full role schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function roleSchema($type = '') {
        if ($this->roleSchema === null) {
            $this->roleSchema = $this->schema($this->fullSchema(), 'Role');
        }
        return $this->schema($this->roleSchema, $type);
    }
}
