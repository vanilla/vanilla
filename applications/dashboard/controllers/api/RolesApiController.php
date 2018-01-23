<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\PermissionsTranslationTrait;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DelimitedScheme;

/**
 * API Controller for the `/roles` resource.
 */
class RolesApiController extends AbstractApiController {
    use PermissionsTranslationTrait;

    /** Maximum number of permission rows that can be displayed before an error is reported. */
    const MAX_PERMISSIONS = 100;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var PermissionModel */
    private $permissionModel;

    /** @var bool Have all permissions been loaded into $renamedPermissions? */
    private $permissionsLoaded = false;

    /** @var RoleModel */
    private $roleModel;

    /** @var Schema */
    private $roleSchema;

    /**
     * RolesApiController constructor.
     *
     * @param RoleModel $roleModel
     * @param PermissionModel $permissionModel
     * @param CategoryModel $categoryModel
     */
    public function __construct(RoleModel $roleModel, PermissionModel $permissionModel, CategoryModel $categoryModel) {
        $this->roleModel = $roleModel;
        $this->permissionModel = $permissionModel;
        $this->categoryModel = $categoryModel;
        $this->nameScheme =  new DelimitedScheme('.', new CamelCaseScheme());
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
     * Simplify the format of a permissions array.
     *
     * @param array $global Global permissions.
     * @param array $categories Category-specific permissions.
     * @return array
     */
    private function formatPermissions(array $global, array $categories) {
        $result = [];

        /**
         * Format an array of permission names. Convert names as necessary and cast values to boolean.
         *
         * @param array $perms
         * @return array
         */
        $format = function(array $perms) {
            $result = [];

            foreach ($perms as $name => $val) {
                if ($this->isPermissionDeprecated($name)) {
                    // Deprecated? Don't need it.
                    continue;
                }

                $name = $this->renamePermission($name);
                $result[$name] = (bool)$val;
            }

            $result = $this->consolidatePermissions($result);

            ksort($result);
            return $result;
        };

        $result[] = [
            'type' => 'global',
            'permissions' => $format($global)
        ];

        foreach ($categories as $cat) {
            // Default category (-1) permissions now fall under an ID of zero (0).
            $catPerms = [
                'id' => $cat['JunctionID'] == -1 ? 0 : $cat['JunctionID'],
                'type' => 'category'
            ];

            // Cleanup non-permission values from the row.
            unset($cat['Name'], $cat['JunctionID'], $cat['JunctionTable'], $cat['JunctionColumn']);

            $catPerms['permissions'] = $format($cat);
            $result[] = $catPerms;
        }

        return $result;
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
            'description:s|n' => [
                'description' => 'Description of the role.',
                'minLength' => 0
            ],
            'type:s|n' => [
                'description' => 'Default type of this role.',
                'minLength' => 0
            ],
            'deletable:b' => 'Is the role deletable?',
            'canSession:b' => 'Can users in this role start a session?',
            'personalInfo:b' => 'Is membership in this role personal information?',
            'permissions:a?' => $this->getPermissionFragment()
        ]);
        return $schema;
    }

    /**
     * Get a single role.
     *
     * @param int $id The ID of the role.
     * @param array $query
     * @throws NotFoundException if unable to find the role.
     * @return array
     */
    public function get($id, array $query) {
        $this->permission('Garden.Settings.Manage');

        $this->idParamSchema();
        $in = $this->schema([
            'expand?' => ApiUtils::getExpandDefinition(['permissions'])
        ], 'in')->setDescription('Get a role.');
        $out = $this->schema($this->roleSchema(), 'out');

        $query = $in->validate($query);

        $row = $this->roleByID($id);
        $row = $this->normalizeOutput($row, $query['expand']);

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
     * Get an array of role permissions, formatted for the API.
     *
     * @param int $roleID
     * @return array
     */
    private function getFormattedPermissions($roleID) {
        $global = $this->permissionModel->getGlobalPermissions($roleID);
        unset($global['PermissionID']);
        $category = $this->permissionModel->getJunctionPermissions(
            ['RoleID' => $roleID],
            'Category'
        );
        $result = $this->formatPermissions($global, $category);
        return $result;
    }

    /**
     * Given a permission name, lookup its legacy equivalent.
     *
     * @param string $permission The new, shortened permission name.
     * @return string|bool
     */
    private function getLegacyPermission($permission) {
        $this->loadAllPermissions();
        $result = array_search($permission, $this->renamedPermissions);
        return $result;
    }

    /**
     * Return a schema to represent a permission row.
     *
     * @return Schema
     */
    public function getPermissionFragment() {
        static $permissionsFragment;

        if ($permissionsFragment === null) {
            $permissionsFragment = $this->schema([
                'id:i?',
                'type:s' => [
                    'enum' => ['global', 'category'],
                ],
                'permissions:o',
            ], 'PermissionFragment');
        }

        return $permissionsFragment;
    }

    /**
     * Get an ID-only role record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamSchema() {
        return $this->schema(['id:i' => 'The role ID.'], 'in');
    }

    /**
     * List roles.
     *
     * @param array $query
     * @return array
     */
    public function index(array $query) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema([
            'expand?' => ApiUtils::getExpandDefinition(['permissions'])
        ], 'in')->setDescription('List roles.');
        $out = $this->schema([':a' => $this->roleSchema()], 'out');

        $query = $in->validate($query);

        $rows = $this->roleModel->getWithRankPermissions()->resultArray();
        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row, $query['expand']);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Fill the $renamedPermissions property with all known permissions.
     */
    private function loadAllPermissions() {
        if ($this->permissionsLoaded !== true) {
            $permissions = array_keys($this->permissionModel->permissionColumns());
            unset($permissions[array_search('PermissionID', $permissions)]);

            foreach ($permissions as $permission) {
                if (!in_array($permissions, $this->deprecatedPermissions)) {
                    // This function will cache a copy of the renamed permission in the property.
                    $this->renamePermission($permission);
                }
            }

            $this->permissionsLoaded = true;
        }
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @throws ServerException If attempting to include permissions, but there are too many permission rows.
     * @param array $dbRecord Database record.
     * @param array|false $expand
     * @return array Return a Schema record.
     */
    protected function normalizeOutput(array $dbRecord, $expand = []) {
        if (array_key_exists('RoleID', $dbRecord)) {
            $roleID = $dbRecord['RoleID'];
            if ($this->isExpandField('permissions', $expand)) {
                $permissionCount = $this->permissionModel
                    ->getWhere(['RoleID' => $roleID], '', 'asc', self::MAX_PERMISSIONS + 1)
                    ->count();
                if ($permissionCount > self::MAX_PERMISSIONS) {
                    throw new ServerException('There are too many permissions to display.', 416);
                }
                $dbRecord['permissions'] = $this->getFormattedPermissions($roleID);
            }
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
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

        $this->idParamSchema();
        $in = $this->rolePostSchema()->setDescription('Update a role.');
        $out = $this->roleSchema('out');

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->roleByID($id);

        if (array_key_exists('permissions', $body)) {
            $this->savePermissions($id, $body['permissions']);
            unset($body['permissions']);
        }

        $roleData = ApiUtils::convertInputKeys($body);
        $roleData['RoleID'] = $id;
        $this->roleModel->save($roleData, ['DoPermissions' => false]);
        $this->validateModel($this->roleModel);
        $row = $this->roleByID($id);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Update permissions on a role.
     *
     * @param $id
     * @param $body
     * @return array
     */
    public function patch_permissions($id, array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema([':a', $this->getPermissionFragment()], 'in')->setDescription('Update permissions on a role');
        $out = $this->schema([':a', $this->getPermissionFragment()], 'out');

        $this->roleByID($id);

        $body = $in->validate($body);
        $this->savePermissions($id, $body);

        $rows = $this->getFormattedPermissions($id);
        $result = $out->validate($rows);

        return $result;
    }

    /**
     * Add a role.
     *
     * @param array $body The request body.
     * @throws ServerException if the role could not be added.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->rolePostSchema()->setDescription('Add a role.');
        $out = $this->schema($this->roleSchema(), 'out');

        $body = $in->validate($body);

        $roleData = ApiUtils::convertInputKeys($body);
        $id = $this->roleModel->save($roleData);
        $this->validateModel($this->roleModel);

        if (!$id) {
            throw new ServerException('Unable to add role.', 500);
        }

        if (array_key_exists('permissions', $body)) {
            $this->savePermissions($id, $body['permissions']);
        }

        $row = $this->roleByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Overwrite all permissions for a role.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function put_permissions($id, array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema([':a', $this->getPermissionFragment()], 'in')->setDescription('Overwrite all permissions for a role.');
        $out = $this->schema([':a', $this->getPermissionFragment()], 'out');

        $this->roleByID($id);

        $body = $in->validate($body);
        $this->savePermissions($id, $body, true);

        $rows = $this->getFormattedPermissions($id);
        $result = $out->validate($rows);

        return $result;
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
     * @return Schema Returns a schema object.
     */
    public function rolePostSchema() {
        static $rolePostSchema;

        if ($rolePostSchema === null) {
            $rolePostSchema = $this->schema(
                Schema::parse([
                    'name',
                    'description?',
                    'type?',
                    'deletable?',
                    'canSession?',
                    'personalInfo?',
                    'permissions?'
                ])->add($this->fullSchema()),
                'RolePost'
            );
        }

        return $this->schema($rolePostSchema, 'in');
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

    /**
     * Save a role's permissions.
     *
     * @param int $roleID The role ID.
     * @param array $rows Permission rows.
     * @param bool $overwrite If a permission isn't explicitly defined, set it to false. All permissions will be overwritten.
     */
    private function savePermissions($roleID, array $rows, $overwrite = false) {
        foreach ($rows as &$row) {
            if (array_key_exists('permissions', $row)) {
                foreach ($row['permissions'] as $perm => $val) {
                    if (array_key_exists($perm, $this->consolidatedPermissions)) {
                        $expanded = array_fill_keys($this->consolidatedPermissions[$perm], (bool)$val);
                        $row['permissions'] = array_merge($row['permissions'], $expanded);
                        unset($row['permissions'][$perm]);
                    }
                }
            }
        }

        if ($overwrite) {
            $this->permissionModel->saveAll([], ['RoleID' => $roleID]);
        }

        $permissions = $this->normalizePermissions($rows, $roleID);
        foreach ($permissions as $perm) {
            // The category model has its own special permission saving routine.
            if (array_key_exists('JunctionTable', $perm) && $perm['JunctionTable'] == 'Category') {
                $this->categoryModel->save([
                    'CategoryID' => $perm['JunctionID'],
                    'CustomPermissions' => true,
                    'Permissions' => [$perm]
                ]);
            } else {
                $this->permissionModel->save($perm);
            }
        }
    }

    /**
     * Given an array of permissions in the API format, alter it to be compatible with the permissions model.
     *
     * @param array $rows
     * @param int $roleID
     * @return array
     */
    private function normalizePermissions(array $rows, $roleID) {
        // Grab allowed global permissions.
        $global = $this->permissionModel->getGlobalPermissions($roleID);
        unset($global['PermissionID']);
        $global = array_keys($global);

        // Grab allowed category permissions.
        $category = $this->permissionModel->getJunctionPermissions(
            ['JunctionID' => -1],
            'Category'
        );
        $category = array_pop($category);
        unset($category['RoleID'], $category['Name'], $category['JunctionTable'], $category['JunctionColumn'], $category['JunctionID']);
        $category = array_keys($category);

        $result = [];
        foreach ($rows as $row) {
            if (!array_key_exists('type', $row)) {
                throw new InvalidArgumentException('The type property could not be found when setting permissions.');
            }
            if (!array_key_exists('permissions', $row)) {
                throw new InvalidArgumentException('The permissions property could not be found when setting permissions.');
            }

            $type = $row['type'];
            $id = array_key_exists('id', $row) ? $row['id'] : false;
            $dbRow = ['RoleID' => $roleID];

            // Ensure the permission names are legitimate and valid for their type.
            foreach ($row['permissions'] as $permission => $value) {
                $legacy = $this->getLegacyPermission($permission);
                if ($legacy === false) {
                    throw new InvalidArgumentException("Unknown permission: {$permission}");
                }
                if ($type === 'global' && !in_array($legacy, $global)) {
                    throw new InvalidArgumentException("Invalid global permission: {$legacy}.");
                } elseif ($type === 'category' && !in_array($legacy, $category)) {
                    throw new InvalidArgumentException("Invalid category permission: {$legacy}.");
                }

                $dbRow[$legacy] = (bool)$value;
            }

            // The API uses 0 for default category permissions. Revert that.
            if ($type === 'category' && $id === 0) {
                $id = -1;
            }

            if ($type === 'category') {
                if (filter_var($id, FILTER_VALIDATE_INT) === false || ($id < 0 && $id !== -1)) {
                    throw new InvalidArgumentException('Category permissions must have a valid ID.');
                }
                $dbRow['JunctionTable'] = 'Category';
                $dbRow['JunctionColumn'] = 'PermissionCategoryID';
                $dbRow['JunctionID'] = $id;
            }

            $result[] = $dbRow;
        }

        return $result;
    }
}
