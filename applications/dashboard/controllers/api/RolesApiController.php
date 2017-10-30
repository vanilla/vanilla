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
use Vanilla\Utility\CamelCaseScheme;

/**
 * API Controller for the `/roles` resource.
 */
class RolesApiController extends AbstractApiController {

    /** @var CamelCaseScheme */
    private $camelCaseScheme;

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var array Groups of permissions that can be consolidated into one. */
    private $consolidatedPermissions = [
        'discussions.moderate' => ['discussions.announce', 'discussions.close', 'discussions.sink'],
        'discussions.manage' => ['discussions.delete', 'discussions.edit']
    ];

    /** @var array Permissions that have been deprecated and should no longer be used. */
    private $deprecatedPermissions = [
        'Garden.Activity.Delete',
        'Garden.Activity.View',
        'Garden.SignIn.Allow',
        'Garden.Curation.Manage',
        'Vanilla.Approval.Require',
        'Vanilla.Comments.Me'
    ];

    /** @var Schema */
    private $idParamSchema;

    /** @var PermissionModel */
    private $permissionModel;

    /** @var array A static mapping of updated permission names. */
    private $renamedPermissions = [
        'Conversations.Moderation.Manage' => 'conversations.moderate',
        'Email.Comments.Add' => 'comments.email',
        'Email.Conversations.Add' => 'conversations.email',
        'Email.Discussions.Add' => 'discussions.email',
        'Garden.Moderation.Manage' => 'community.moderate',
        'Garden.NoAds.Allow' => 'noAds.use',
        'Garden.Settings.Manage' => 'site.manage',
        'Garden.Users.Approve' => 'applicants.manage',
        'Groups.Group.Add' => 'groups.add',
        'Groups.Moderation.Manage' => 'groups.moderate',
        'Reputation.Badges.Give' => 'badges.moderate',
        'Vanilla.Tagging.Add' => 'tags.add'
    ];

    /** @var array These permissions should not be renamed. */
    private $fixedPermissions = [
        'Reactions.Negative.Add',
        'Reactions.Positive.Add'
    ];
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
     * @param PermissionModel $permissionModel
     */
    public function __construct(RoleModel $roleModel, PermissionModel $permissionModel) {
        $this->roleModel = $roleModel;
        $this->permissionModel = $permissionModel;
        $this->caseScheme = new CapitalCaseScheme();
        $this->camelCaseScheme = new CamelCaseScheme();
    }

    /**
     * Collapse multiple permissions down into a single one, where possible.
     *
     * @param array $permissions
     * @return array
     */
    private function consolidatePermissions(array $permissions) {
        $result = $permissions;

        foreach ($this->consolidatedPermissions as $name => $perms) {
            $pass = 0;
            $total = count($perms);
            foreach ($perms as $currentPerm) {
                if (array_key_exists($currentPerm, $permissions) && $permissions[$currentPerm]) {
                    $pass++;
                }
            }

            if ($pass == $total) {
                $val = true;
            } elseif ($pass == 0) {
                $val = false;
            } else {
                $val = null;
            }

            // If we had all or none of the child permissions, remove them. Only include the parent.
            if ($val !== null) {
                foreach ($perms as $currentPerm) {
                    unset($result[$currentPerm]);
                }
            }

            $result[$name] = $val;
            unset($currentPerm, $pass);
        }

        return $result;
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
        $result = [
            'global' => [],
            'categories' => []
        ];

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

        $result['global'] = $format($global);

        foreach ($categories as $cat) {
            // Default category (-1) permissions now fall under an ID of zero (0).
            $catPerms['id'] = $cat['JunctionID'] == -1 ? 0 : $cat['JunctionID'];

            // Cleanup non-permission values from the row.
            unset($cat['Name'], $cat['JunctionID'], $cat['JunctionTable'], $cat['JunctionColumn']);

            $catPerms['permissions'] = $format($cat);
            $result['categories'][] = $catPerms;
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
     * Determine if a permission slug is deprecated.
     *
     * @param string $permission
     * @return bool
     */
    private function isPermissionDeprecated($permission) {
        $result = in_array($permission, $this->deprecatedPermissions);
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
            $global = $this->permissionModel->getGlobalPermissions($roleID);
            unset($global['PermissionID']);
            $category = $this->permissionModel->getJunctionPermissions([
                'RoleID' => $roleID,
                'Category'
            ]);
            $row['permissions'] = $this->formatPermissions($global, $category);
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

        $this->idParamSchema('in');
        $in = $this->rolePostSchema('in')->setDescription('Update a role.');
        $out = $this->roleSchema('out');

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->roleByID($id);
        $roleData = $this->caseScheme->convertArrayKeys($body);
        $roleData['RoleID'] = $id;
        $this->roleModel->save($roleData);
        $this->validateModel($this->roleModel);
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
        $this->validateModel($this->roleModel);

        if (!$id) {
            throw new ServerException('Unable to add role.', 500);
        }

        $row = $this->roleByID($id);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return new Data($result, 201);
    }

    /**
     * Rename a legacy Vanilla permission slug.
     *
     * @param string $permission
     * @return string
     */
    private function renamePermission($permission) {
        if (array_key_exists($permission, $this->renamedPermissions)) {
            // Already got a mapping for this permission? Go ahead and use it.
            $result = $this->renamedPermissions[$permission];
        } else {
            // Time to format the permission name.
            $segments = explode('.', $permission);

            // Pop the application off the top, if it seems safe to do so.
            if (!in_array($permission, $this->fixedPermissions) && count($segments) == 3) {
                unset($segments[0]);
            }

            foreach ($segments as &$seg) {
                $seg = $this->camelCaseScheme->convert($seg);
            }

            // Cache the renamed permission for this request.
            $result = implode('.', $segments);
            $this->renamedPermissions[$permission] = $result;
        }

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
