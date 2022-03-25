<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\Controller;

/**
 * Handles the `/role-requests` endpoints.
 */
class RoleRequestsApiController extends Controller {
    public const DEFAULT_TTL = '5 days';

    /**
     * @var RoleRequestModel
     */
    private $requestModel;

    /**
     * @var RoleRequestMetaModel
     */
    private $metaModel;

    /**
     * @var \RoleModel
     */
    private $roleModel;

    /**
     * @var \UserModel
     */
    private $userModel;

    /**
     * RoleRequestsApiController constructor.
     *
     * @param RoleRequestModel $requestModel
     * @param RoleRequestMetaModel $metaModel
     * @param \RoleModel $roleModel
     * @param \UserModel $userModel
     */
    public function __construct(RoleRequestModel $requestModel, RoleRequestMetaModel $metaModel, \RoleModel $roleModel, \UserModel $userModel) {
        $this->requestModel = $requestModel;
        $this->metaModel = $metaModel;
        $this->roleModel = $roleModel;
        $this->userModel = $userModel;
    }

    /**
     * Create a request meta.
     *
     * @param array $body
     * @return Data
     */
    public function put_metas(array $body): Data {
        $this->permission('Garden.Settings.Manage');

        $in = Schema::parse(
            ['roleID', 'type', 'name', 'body', 'format', 'attributesSchema:o', 'attributes?' => $this->metaModel->getAttributesSchema()]
        )->add($this->metaModel->getWriteSchema());
        $body = $in->validate($body);

        $this->metaModel->insert($body);
        $result = $this->metaModel->selectSingle(ArrayUtils::pluck($body, ['roleID', 'type']));

        return new Data($this->filterMetaRow($result));
    }

    /**
     * List request metas.
     *
     * @param array $query
     * @return Data
     */
    public function index_metas(array $query): Data {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'roleID:i?',
            'type:s?' => [
                'enum' => [RoleRequestModel::TYPE_APPLICATION, RoleRequestModel::TYPE_INVITATION],
            ],
            'hasRole:b?',
            'expand?' => ApiUtils::getExpandDefinition(['role', 'roleRequest']),
        ], 'in');
        $query = $in->validate($query);
        $data = array_map([$this, 'filterMetaRow'], $this->metaModel->select(ArrayUtils::pluck($query, ['roleID', 'type'])));
        if (isset($query['hasRole'])) {
            $hasRole = $query['hasRole'];
            $roleIDs = $this->userModel->getRoleIDs($this->getSession()->UserID);
            $data = array_filter($data, function ($row) use ($hasRole, $roleIDs) {
                $r = in_array($row['roleID'], $roleIDs);
                return $hasRole ? $r : !$r;
            });
        }

        $this->expandMeta($data, $query['expand'] ?? []);

        return new Data($data);
    }

    /**
     * Get a single meta item.
     *
     * @param string $type
     * @param int $roleID
     * @return Data
     */
    public function get_metas(string $type, int $roleID): Data {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'type:s' => ['enum' => [RoleRequestModel::TYPE_APPLICATION, RoleRequestModel::TYPE_INVITATION]],
            'roleID:i'
        ]);
        $query = $in->validate(['type' => $type, 'roleID' => $roleID]);

        try {
            $data = $this->filterMetaRow($this->metaModel->selectSingle($query));
            $rows = [&$data];
            $this->expandMeta($rows, ['expand' => 'all']);
        } catch (NoResultsException $ex) {
            throw new NotFoundException('Application', [HttpException::FIELD_DESCRIPTION => 'The role does not support applications.']);
        }
        return new Data($data);
    }

    /**
     * Delete a single meta item.
     *
     * @param string $type
     * @param int $roleID
     * @return Data
     */
    public function delete_metas(string $type, int $roleID): Data {
        $this->permission('Garden.Settings.Manage');

        $this->metaModel->delete($this->metaModel->primaryWhere($roleID, $type));

        return new Data(null);
    }

    /**
     * Apply to a role.
     *
     * @param array $body
     * @return Data Data object for a web response;
     */
    public function post_applications(array $body): Data {
        $this->permission('Garden.SignIn.Allow');

        $in = Schema::parse(
            ['roleID', 'attributes']
        )
            ->add($this->requestModel->getWriteSchema())
            ->setField('properties.attributes', ['type' => 'object']);

        $body = $in->validate($body);
        $body += [
            'type' => RoleRequestModel::TYPE_APPLICATION,
            'userID' => $this->getSession()->UserID,
            'status' => RoleRequestModel::STATUS_PENDING,
        ];

        $id = $this->requestModel->insert($body);
        $result = $this->getInternal($id);
        return $result;
    }

    /**
     * List all role requests.
     *
     * @param array $query
     * @return Data
     * @throws HttpException Failed API call.
     * @throws PermissionException Failed Permissions.
     * @throws \Garden\Schema\ValidationException Data failed to validate.
     */
    public function index(array $query = []): Data {
        $this->permission('Garden.SignIn.Allow');

        $in = Schema::parse([
            'type:s?' => [
                'enum' => [RoleRequestModel::TYPE_APPLICATION, RoleRequestModel::TYPE_INVITATION],
                'x-filter' => [
                    'field' => 'type',
                ],
            ],
            'roleID:i?' => [
                'x-filter' => [
                    'field' => 'roleID',
                ],
            ],
            'status:s?' => [
                'enum' => [RoleRequestModel::STATUS_PENDING, RoleRequestModel::STATUS_APPROVED, RoleRequestModel::STATUS_DENIED],
                'x-filter' => [
                    'field' => 'status',
                ],
            ],
            'userID:i?' => [
                'x-filter' => [
                    'field' => 'userID',
                ],
            ],
            'roleRequestID?' => RangeExpression::createSchema([':i'])->setField('x-filter', ['field' => 'roleRequestID']),
            'page:i?' => [
                'minimum' => 1,
                'maximum' => 100,
            ],
            'offset:i?' => [
                'minimum' => 0,
                'maximum' => 3000,
            ],
            'limit:i?' => [
                'default' => 30,
                'minimum' => 1,
                'maximum' => 100,
            ],
            'sort:s?' => [
                'default' => 'roleRequestID',
                'enum' => ApiUtils::sortEnum('dateInserted', 'dateOfStatus', 'roleRequestID'),
            ],
            'expand?' => ApiUtils::getExpandDefinition(['user', 'role']),
        ])->addValidator('', SchemaUtils::onlyOneOf(['page', 'offset']));

        $query = $in->validate($query);
        // Members lower than community managers can only view their own requests.
        if (!$this->getSession()->getPermissions()->has('Garden.Community.Manage')) {
            $query['userID'] = $this->getSession()->UserID;
        }

        $where = ApiUtils::queryToFilters($in, $query);
        [$offset, $limit] = ApiUtils::offsetLimit($query);

        $rows = $this->requestModel->select($where, ['orderFields' => $query['sort'], 'limit' => $limit, 'offset' => $offset]);
        $rows = array_map([$this, 'filterRequestRow'], $rows);

        // Join the other data in.
        $this->expand($rows, $query['expand'] ?? []);

        return new Data($rows);
    }

    /**
     * Get a single role request.
     *
     * @param int $id
     * @param array $query
     * @return Data
     */
    public function get(int $id, array $query): Data {
        $this->permission('Garden.Community.Manage');

        return $this->getInternal($id, $query);
    }

    /**
     * List just the role applications.
     *
     * @param array $query
     * @return Data
     */
    public function index_applications(array $query = []): Data {
        $query = ['type' => RoleRequestModel::TYPE_APPLICATION] + $query;
        $result = $this->index($query);
        return $result;
    }

    /**
     * An alias of `get` that only looks at applications.
     *
     * @param int $roleRequestID
     * @param array $query
     * @return Data
     */
    public function get_applications(int $roleRequestID, array $query): Data {
        $result = $this->get($roleRequestID, $query);
        if ($result['type'] !== RoleRequestModel::TYPE_APPLICATION) {
            throw new NotFoundException("The specified ID is not an application");
        }
        return $result;
    }

    /**
     * Approve or deny a role application.
     *
     * @param int $roleRequestID
     * @param array $body
     * @param array $query
     * @return Data
     * @throws NoResultsException No role request found.
     * @throws PermissionException User does not have permission to assign roles or update applications.
     * @throws \Garden\Schema\ValidationException Data passed to API is invalid.
     * @throws HttpException Failed API call.
     */
    public function patch_applications(int $roleRequestID, array $body, array $query = []): Data {
        $this->permission('Garden.Community.Manage');

        $row = $this->requestModel->selectSingle(['roleRequestID' => $roleRequestID]);
        $roleID = $row['roleID'];

        if (!$this->roleModel->canUserAssign($this->getSession()->UserID, $roleID)) {
            throw new PermissionException('Garden.Settings.Manage');
        }

        $in = Schema::parse(
            ['status']
        )->add($this->requestModel->getWriteSchema());
        $body = $in->validate($body);

        // Set the expiry for closed applications.
        if (in_array($body['status'], [RoleRequestModel::STATUS_APPROVED, RoleRequestModel::STATUS_DENIED])) {
            $body['ttl'] = self::DEFAULT_TTL;
        } else {
            $body['dateExpires'] = null;
        }

        $this->requestModel->update(
            $body,
            ['roleRequestID' => $roleRequestID, 'status <>' => $body['status']]
        );
        $result = $this->getInternal($roleRequestID, $query);
        return $result;
    }

    /**
     * Get a single row without a permission check.
     *
     * @param array $where The where clause to find the record.
     * @param array $query
     * @return Data
     * @throws NoResultsException Failed to retrieve results.
     * @throws \Garden\Schema\ValidationException Data failed to validate.
     */
    protected function getInternalWhere(array $where, array $query = []): Data {
        $in = Schema::parse([
            'expand?' => ApiUtils::getExpandDefinition(['user', 'role']),
        ]);
        $query = $in->validate($query);

        $row = $this->requestModel->selectSingle($where);
        $row = $this->filterRequestRow($row);
        $rows = [&$row];
        $this->expand($rows, $query['expand'] ?? []);

        return new Data($row);
    }

    /**
     * Get a single row without a permission check.
     *
     * @param int $roleRequestID
     * @param array $query
     * @return Data
     * @throws NoResultsException Failed to retrieve results.
     * @throws \Garden\Schema\ValidationException Data failed to validate.
     */
    protected function getInternal(int $roleRequestID, array $query = []): Data {
        $where = $this->requestModel->primaryWhere($roleRequestID);

        $result = $this->getInternalWhere($where, $query);
        return $result;
    }

    /**
     * Filter a meta row from the model to the API.
     *
     * @param array $row
     * @return array
     */
    private function filterMetaRow(array $row) {
        $r = ArrayUtils::pluck(
            $row,
            [
                'roleID', 'type', 'name', 'body', 'format', 'attributesSchema', 'attributes',
                'dateInserted', 'insertUserID', 'dateUpdated', 'updateUserID',
            ]
        );
        $folder = $row['type'] === RoleRequestModel::TYPE_INVITATION ? 'role-invitations' : 'role-applications';
        $r['url'] = url("/requests/$folder?role={$row['roleID']}", true);
        return $r;
    }

    /**
     * Filter a role request row to data that is appropriate for the request.
     *
     * @param array $row
     * @return array
     */
    private function filterRequestRow(array $row) {
        $extended = $this->getSession()->checkPermission('Garden.Community.Manage');

        $fields = [
            'roleRequestID', 'type', 'status', 'roleID', 'userID', 'dateExpires', 'attributes',
            'dateInserted', 'insertUserID', 'dateUpdated', 'updateUserID',
        ];
        if ($extended) {
            $fields = array_merge($fields, ['dateOfStatus', 'statusUserID']);
        }
        return ArrayUtils::pluck($row, $fields);
    }

    /**
     * Expand the results of an API call.
     *
     * @param array $rows
     * @param array|true $query
     */
    private function expand(array &$rows, $query): void {
        ModelUtils::leftJoin($rows, ModelUtils::expandedFields('role', $query), [$this->roleModel, 'fetchFragments']);

        $userFields = $this->getSession()->checkPermission('Garden.Community.Manage') ? ['user', 'statusUser'] : ['user'];
        ModelUtils::leftJoin($rows, ModelUtils::expandedFields($userFields, $query), [$this->userModel, 'fetchFragments']);
    }

    /**
     * Expand fields from the request meta.
     *
     * @param array $rows
     * @param array|true $query
     */
    private function expandMeta(array &$rows, $query): void {
        ModelUtils::leftJoin($rows, ModelUtils::expandedFields('role', $query), [$this->roleModel, 'fetchFragments']);
        if (ModelUtils::isExpandOption('roleRequest', $query)) {
            $roleIDs = $this->userModel->getRoleIDs($this->getSession()->UserID);
            foreach ($rows as &$row) {
                $row['hasRole'] = in_array($row['roleID'], $roleIDs);
            }

            ModelUtils::leftJoin(
                $rows,
                ['roleID' => 'roleRequest'],
                $this->requestModel->fetchFragmentsFunction(RoleRequestModel::TYPE_APPLICATION, $this->getSession()->UserID)
            );
        }
    }
}
