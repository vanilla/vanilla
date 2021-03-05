<?php
/**
 * @author Dani Stark<dani.stark@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\ValidationField;
use Vanilla\Addons\Pockets\PocketsModel;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Garden\Web\Exception\ServerException;
use Vanilla\Utility\PocketService;

/**
 * Class PocketsApiController
 */
class PocketsApiController extends \AbstractApiController {

    /** @var PocketsModel */
    private $pocketsModel;

    /** @var Schema */
    private $idParamSchema;

    /** @var Schema */
    private $pocketPostSchema;

    /** @var Schema */
    private $pocketPatchSchema;

    /** @var PocketService  */
    private $pocketService;

    /**
     * PocketsApiController constructor.
     *
     * @param PocketsModel $pocketsModel
     * @param PocketService $pocketService
     */
    public function __construct(PocketsModel $pocketsModel, PocketService $pocketService) {
        $this->pocketsModel = $pocketsModel;
        $this->pocketService = $pocketService;
    }

    /**
     * Get a list of all pockets.
     *
     * @param array $query
     * @return  array
     */
    public function index(array $query): array {
        $this->permission('Plugins.Pockets.Manage');
        $in = $this->schema(['expand?' => ApiUtils::getExpandDefinition(['body'])], 'in');
        $out = $this->schema([":a" => $this->fullPocketSchema()], "out");
        $query = $in->validate($query);
        $rows = $this->pocketsModel->getAll();
        foreach ($rows as &$row) {
            $row = $this->pocketsModel->normalizeOutput($row, $query);
        }
        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a pocket by ID.
     *
     * @param int $id The pocket id.
     * @param array $query
     * @return array|object
     * @throws Gdn_UserException Pocket not found.
     */
    public function get(int $id, array $query) {
        $this->permission('Plugins.Pockets.Manage');
        $this->idParamSchema();
        $in = $this->schema([
            'expand:s?' => ApiUtils::getExpandDefinition(['body'])
        ], 'in')->setDescription('Get a pocket.');
        $query = $in->validate($query);
        $out = $this->schema($this->fullPocketSchema(), 'out');
        $row = $this->pocketsModel->getID($id);
        if (!$row) {
            throw notFoundException('Pocket');
        }
        $row = $this->pocketsModel->normalizeOutput($row, $query);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Delete a pocket.
     *
     * @param int $id The ID of the pocket.
     */
    public function delete(int $id) {
        $this->permission('Plugins.Pockets.Manage');
        $in = $this->idParamSchema();
        $out = $this->schema([], 'out');
        $this->pocketByID($id);
        $this->pocketsModel->delete(['pocketID' => $id], ['limit' => 1]);
    }

    /**
     * Enable/Disable a pocket.
     *
     * @param int $id The pocket id.
     * @param array $body
     */
    public function patch_status($id, array $body) {
        $this->permission('Plugins.Pockets.Manage');
        $this->idParamSchema();
        $in = $this->schema([
            'enable:b' =>   [
                'default' => false,
                "type" => "boolean",
            ]
        ], 'in')->setDescription('Enable or disable a pocket.');
        $body = $in->validate($body);
        $row = $this->pocketByID($id);
        if (array_key_exists('enable', $body)) {
            $body['enable'] = ($body['enable'] === true) ? 0 : 1;
        }
        if ($row && isset($body['enable'])) {
            $this->pocketsModel->save(['PocketID' => $id, 'Disabled' => $body['enable']]);
        }
    }

    /**
     * Add a pocket.
     *
     * @param array $body The request body.
     * @throws ServerException If the pocket could not be added.
     * @return array
     */
    public function post(array $body): array {
        $this->permission('Plugins.Pockets.Manage');
        $in = $this->schema($this->pocketPostSchema(), 'in');
        $out = $this->schema($this->fullPocketSchema(), 'out');
        $body = $in->validate($body);
        $body = $this->pocketsModel->normalizeInput($body);

        $pocketData = ApiUtils::convertInputKeys($body);

        $pocketID = $this->pocketsModel->save($pocketData);
        $this->validateModel($this->pocketsModel);
        if (!$pocketID) {
            throw new ServerException('Unable to add pocket.', 500);
        }
        $row = $this->pocketByID($pocketID);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a pocket schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function pocketPostSchema($type = ''): Schema {
        if ($this->pocketPostSchema === null) {
            $pocketPostSchema =  $this->schema(
                Schema::parse([
                    "name:s" => [
                        "type" => "string",
                    ],
                    "body:s" => [
                        "type" => "string",
                    ],
                    'repeatType:s' => [
                        'enum' => [
                            'once',
                            'after',
                            'before',
                            'every',
                            'index'
                        ],
                    ],
                    'widgetID:s|n?',
                    "page:s?" => [
                        'enum' => array_merge($this->pocketsModel::getPages(), $this->pocketService->getPages()),
                    ],
                    'repeatEvery:i|n?' => 'Repeat frequency',
                    'repeatIndexes:a|n?' => 'Repeat indexes',
                    'mobileType:s?' => [
                        'enum' => ['only', 'never', 'default']
                    ],
                    'isDashboard:b?' => [
                        'default' => false
                    ],
                    'sort:i?' => [
                        'default' => 0
                    ],
                    'isEmbeddable:b?' => [
                        'default' => false
                    ],
                    'location:s?' => [
                        'default' => 'Panel',
                        'enum' => $this->pocketsModel->getLocationsArray(true)
                    ],
                    'isAd:b?' => [
                        'default' => false
                    ],
                    'enabled:b' => [
                        'default' => false
                    ],
                    'categoryID:i|n?' => 'The numeric ID of a category.',
                    'includeChildCategories:i|n?',
                    "format:s" => [
                        'enum' => ['raw', 'widget']
                    ],
                    'roleIDs:a?' => [
                        'items' => [
                            'type' => 'integer',
                        ]
                    ],
                ])->add($this->fullPocketSchema()),
                'PocketPost'
            )->addValidator('roleIDs', [$this, 'validateRoleIDs']);
            if ($extendSchema = $this->pocketService->getSchema()) {
                $pocketPostSchema->merge($extendSchema);
            }
            $this->pocketPostSchema = $pocketPostSchema;
        }
        return $this->schema($this->pocketPostSchema, $type);
    }

    /**
     * Update a pocket.
     *
     * @param int $id The ID of the pocket.
     * @param array $body The request body.
     * @return array
     * @throw NotFoundException If the pocket we are updating could not be found.
     */
    public function patch(int $id, array $body = []): array {
        $this->permission('Plugins.Pockets.Manage');
        $this->idParamSchema('in');
        $in = $this->schema($this->pocketPatchSchema(), 'in');
        $out = $this->schema($this->fullPocketSchema(), 'out');

        $body = $in->validate($body);
        // // Make sure the pocket exists.
        $this->pocketByID($id);
        $body = $this->pocketsModel->normalizeInput($body);
        $pocketData = ApiUtils::convertInputKeys($body);
        $pocketData['PocketID'] = $id;
        $this->pocketsModel->save($pocketData);
        $this->validateModel($this->pocketsModel);
        $row = $this->pocketByID($id);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a pocket for editing.
     *
     * @param int $id The ID of the pocket.
     * @throws NotFoundException If the pocket is not found.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Plugins.Pockets.Manage');
        $this->idParamSchema()->setDescription('Get a pocket for editing.');
        $out = $this->schema(
            Schema::parse([
                'pocketID',
                'widgetID',
                'name',
                'page',
                'body',
                'format',
                'categoryID?'
            ])->add($this->fullPocketSchema()),
            ['PocketGetEdit', 'out']
        );
        $row = $this->pocketByID($id);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a pocket by its numeric ID.
     *
     * @param int $id The pocket ID.
     * @throws NotFoundException If the pocket could not be found.
     * @return array
     */
    private function pocketByID($id): array {
        $row = $this->pocketsModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Pocket');
        }
        return $row;
    }

    /**
     * Get a pocket patch schema.
     *
     * @param string $type
     * @return Schema Returns a schema object.
     */
    private function pocketPatchSchema(string $type = ""): Schema {
        if ($this->pocketPatchSchema === null) {
            $this->pocketPatchSchema = $this->schema(Schema::parse([
                'name:s?',
                'mobileType:s?',
                'isDashboard:b?',
                'isEmbeddable:b?',
                'isAd:b?',
                'enabled:b?'
            ])->add($this->fullPocketSchema()), 'PocketPatch');
        }
        return $this->schema($this->pocketPatchSchema, $type);
    }

    /**
     * Validate roleIDs.
     *
     * @param array $roleIDs RoleIds
     * @param ValidationField $validationField Validation field.
     * @return bool
     */
    public function validateRoleIDs($roleIDs, ValidationField $validationField): bool {
        $valid = true;
        $roles = RoleModel::roles();
        foreach ($roleIDs as $roleID) {
            if (!in_array($roleID, array_column($roles, 'RoleID'))) {
                $valid = false;
                break;
            }
        }
        return $valid;
    }

    /**
     * Get an id record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function idParamSchema($type = 'in'): Schema {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The pocket ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Get a schema of all available Pocket fields
     *
     * @return Schema
     */
    public function fullPocketSchema(): Schema {
            $schema = $this->schema([
                "pocketID:i" => [
                    "description" => "The ID of the pocket.",
                    "type" => "integer",
                ],
                "widgetID:s|n" => [
                    "description" => "The widget type.",
                ],
                "name:s" => [
                    "description" => "The name of the pocket.",
                    "type" => "string",
                ],
                "page:s?" => [
                    'description' => 'Which page to display the Pocket on.',
                    'enum' => array_merge($this->pocketsModel::getPages(), $this->pocketService->getPages()),
                ],
                "body:s?" => [
                    "description" => 'The body of the pocket.',
                    "type" => "string",
                ],
                'disabled:i' => [
                    'description' => 'Pocket enabled/disabled.',
                    'default' => 1,
                ],
                'sort:i' => [
                    'default' => 0,
                    'description' => 'The pocket sort order.'
                ],
                'repeat:s' => 'Pocket repeat type.',
                'mobileOnly:i' => [
                    'default' => 0,
                    'description' => 'Pocket active on mobile only.'
                ],
                'mobileNever:i' => [
                    'default' => 0,
                    'description' => 'Pocket not active on mobile.'
                ],
                'embeddedNever:i' => [
                    'default' => 0,
                    'description' => 'Pocket embedding disabled.'
                ],
                'showInDashboard:i' => [
                    'default' => 0,
                    'description' => 'Pocket active in dashboard.'
                ],
                'testMode:i' => [
                    'default' => 0,
                    'description' => 'If the pocket is in test mode.'
                ],
                "format:s" => [
                    'description' => "The pocket format.",
                    'enum' => ['raw', 'widget']
                ],
                'location:s?' => [
                    'default' => 'Panel',
                    'enum' => $this->pocketsModel->getLocationsArray(true),
                    'description' => 'Location of the pocket on the page.'
                ],
                'type:s' => [
                    'description' => "Pocket type.",
                    'enum' => ['default', 'ad'],
                    ],
                ], 'Pocket');
        return $schema;
    }
}
