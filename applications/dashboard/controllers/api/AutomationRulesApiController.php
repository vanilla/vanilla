<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Garden\Container\ContainerException;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\AutomationRules\EscalationRuleService;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\AutomationRules\Schema\AutomationRuleInputSchema;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Web\APIExpandMiddleware;
use Vanilla\Web\Controller;

/**
 * Automation api-controller
 */
class AutomationRulesApiController extends Controller
{
    private AutomationRuleService $automationRuleService;
    private EscalationRuleService $escalationRuleService;
    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;
    private AutomationRuleModel $automationRuleModel;

    private Schema $idParamSchema;

    /**
     * AutomationRules constructor
     *
     * @param AutomationRuleService $automationRuleService
     * @param AutomationRuleModel $automationRuleModel
     * @param AutomationRuleDispatchesModel $automationRuleDispatchesModel
     */
    public function __construct(
        AutomationRuleService $automationRuleService,
        EscalationRuleService $escalationRuleService,
        AutomationRuleModel $automationRuleModel,
        AutomationRuleDispatchesModel $automationRuleDispatchesModel
    ) {
        $this->automationRuleService = $automationRuleService;
        $this->escalationRuleService = $escalationRuleService;
        $this->automationRuleModel = $automationRuleModel;
        $this->automationRuleDispatchesModel = $automationRuleDispatchesModel;
    }

    /**
     * Get automation catalog
     * @param array $query
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_catalog(array $query = []): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = Schema::parse([
            "escalations:b?" => [
                "default" => false,
                "description" => "Filter by escalation triggers and actions.",
            ],
        ]);

        $query = $in->validate($query);

        $out = Schema::parse(["triggers:o", "actions:o"]);

        if ($query["escalations"]) {
            $triggers = $this->escalationRuleService->getEscalationTriggers();
            $actions = $this->escalationRuleService->getEscalationActions();
        } else {
            $triggers = $this->automationRuleService->getAutomationTriggers();
            $actions = $this->automationRuleService->getAutomationActions();
        }
        $triggerActionSchema = [];
        foreach ($triggers as $trigger) {
            $schema = $trigger::getSchema();
            $triggerActionSchema["triggers"][$trigger::getType()] = $trigger::getBaseSchemaArray();
            if (!empty($schema->getSchemaArray())) {
                $triggerActionSchema["triggers"][$trigger::getType()]["schema"] = $schema;
            }
        }
        foreach ($actions as $action) {
            try {
                $schema = $action::getSchema();
                $triggerActionSchema["actions"][$action::getType()] = $action::getBaseSchemaArray();
                if (!empty($schema->getSchemaArray())) {
                    $triggerActionSchema["actions"][$action::getType()]["schema"] = $schema;
                }
            } catch (\Exception $e) {
                // Don't break return available schema
                unset($triggerActionSchema["actions"][$action::getType()]);
            }
        }

        $result = $out->validate($triggerActionSchema);
        return new Data($result);
    }

    /**
     * Get meta data for a specific automation rule action.
     *
     * @param array $query
     * @return Data
     */
    public function get_actionByType(array $query)
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(["type:s" => "Type used to look up automation action"], ["ActionByTypeGet", "in"]);

        $query = $in->validate($query);

        /** @var class-string<AutomationAction>|null $action */
        $action = $this->automationRuleService->getAction($query["type"]);

        if (is_null($action)) {
            throw new NotFoundException("Action");
        }

        $actionSchema = $action::getBaseSchemaArray();
        $schema = $action::getSchema();

        $dynamicSchema = $action::getDynamicSchema($query);
        if (!empty($dynamicSchema)) {
            $actionSchema["dynamicSchema"] = $dynamicSchema;
        }

        if (!empty($schema->getSchemaArray())) {
            $actionSchema["schema"] = $schema;
        }

        return new Data($actionSchema);
    }

    /**
     * Get automation recipes
     *
     * @param array $query
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_recipes(array $query = []): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(new AutomationRuleInputSchema());
        $query = $in->validate($query);
        $out = $this->schema(
            [
                ":a" => $this->automationRuleModel->getAutomationRuleSchema(),
            ],
            "out"
        );
        $result = $out->validate($this->automationRuleModel->getAutomationRules($query));

        return new Data($result);
    }

    /**
     * Get a single automation recipe
     *
     * @param int $id
     * @param array $query
     * @return Data
     * @throws ValidationException
     * @throws NotFoundException
     * @throws PermissionException|HttpException
     */
    public function get_recipe(int $id, array $query): Data
    {
        $this->permission("Garden.Settings.Manage");
        $this->idParamSchema();
        $in = $this->schema(
            ["expand?" => ApiUtils::getExpandDefinition(["insertUser", "updateUser", "dispatchStatus"])],
            ["RecipeGet", "in"]
        )->setDescription("Get an automation recipe.");

        $query = $in->validate($query);
        $out = $this->schema($this->automationRuleModel->getAutomationRuleSchema(), "out");
        $query["automationRuleID"] = $id;
        $record = $this->automationRuleModel->getAutomationRules($query);
        if (empty($record)) {
            throw new NotFoundException("Automation rule not found.");
        }
        $result = $out->validate(array_shift($record));
        return new Data($result);
    }

    /**
     * Get an ID-only recipe record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema(string $type = "in")
    {
        if (empty($this->idParamSchema)) {
            $this->idParamSchema = $this->schema(Schema::parse(["id:i" => "The recipe ID."]), $type);
        }

        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * Add new automation recipe
     *
     * @param array $body
     * @return Data
     * @throws ClientException
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws PermissionException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    public function post(array $body): Data
    {
        $this->permission("Garden.Settings.Manage");

        if (empty($body["trigger"]) || empty($body["action"])) {
            throw new ClientException("Trigger and action are required.");
        }
        $triggerType = $body["trigger"]["triggerType"] ?? "";
        $actionType = $body["action"]["actionType"] ?? "";
        $in = $this->schema($this->getAutomationRulePostPatchSchema($triggerType, $actionType))->addValidator(
            "name",
            $this->automationRuleModel->validateName()
        );
        $body = $in->validate($body);
        $body = $this->getFormattedAutomationRule($body);
        $automationRuleID = $this->automationRuleModel->saveAutomationRule($body);

        $out = $this->schema($this->automationRuleModel->getAutomationRuleSchema(), "out");
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
        return new Data($out->validate($automationRule));
    }

    /**
     * Update an existing recipe
     *
     * @param int $id
     * @param array $body
     * @return Data
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    public function patch(int $id, array $body = []): Data
    {
        $this->permission("Garden.Settings.Manage");
        $existingRecipe = $this->automationRuleModel->getAutomationRuleByID($id);
        if ($existingRecipe["status"] === AutomationRuleModel::STATUS_DELETED) {
            throw new NotFoundException("Automation rule not found.");
        }
        $in = $this->schema(
            $this->getAutomationRulePostPatchSchema(
                $body["trigger"]["triggerType"] ?? "",
                $body["action"]["actionType"] ?? "",
                "patch"
            )
        )->addValidator("name", $this->automationRuleModel->validateName($id));
        $body = $in->validate($body);
        $body = $this->getFormattedAutomationRule($body);
        $this->automationRuleModel->saveAutomationRule($body, $id);
        $out = $this->schema($this->automationRuleModel->getAutomationRuleSchema(), "out");
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($id);
        return new Data($out->validate($automationRule));
    }

    /**
     * Delete an existing automation recipe
     *
     * @param int $id
     * @return void
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws PermissionException
     * @throws \Garden\Container\NotFoundException
     */
    public function delete(int $id): void
    {
        $this->permission("Garden.Settings.Manage");
        $this->idParamSchema()->setDescription("Delete an automation recipe.");
        $this->automationRuleModel->getAutomationRuleByID($id);
        $this->automationRuleModel->deleteAutomationRule($id);
    }

    /**
     * Format the input data into the form required for saving
     *
     * @param array $body
     * @return array
     */
    private function getFormattedAutomationRule(array $body): array
    {
        $automationRule = [
            "name" => $body["name"],
            "triggerType" => $body["trigger"]["triggerType"],
            "triggerValue" => $body["trigger"]["triggerValue"],
            "actionType" => $body["action"]["actionType"],
            "actionValue" => $body["action"]["actionValue"] ?? [],
        ];
        if (isset($body["status"])) {
            $automationRule["status"] = $body["status"];
        }
        return $automationRule;
    }

    /**
     * Return schema for the recipe post endpoint
     *
     * @param string $triggerType
     * @param string $actionType
     * @param string $type
     *
     * @return Schema
     */
    private function getAutomationRulePostPatchSchema(
        string $triggerType,
        string $actionType,
        string $type = "post"
    ): Schema {
        $schema = Schema::parse([
            "name:s",
            "trigger:o" => ["triggerType:s", "triggerValue:o"],
            "action:o" => ["actionType:s", "actionValue:o"],
            "status:s?" => [
                "enum" => [AutomationRuleModel::STATUS_ACTIVE, AutomationRuleModel::STATUS_INACTIVE],
            ],
        ]);

        $activeAutomationTriggers = $this->automationRuleService->getAutomationTriggers();
        $activeAutomationActions = $this->automationRuleService->getAutomationActions();
        if (
            !empty($triggerType) &&
            !empty($actionType) &&
            !empty($activeAutomationTriggers[$triggerType]) &&
            !empty($activeAutomationActions[$actionType])
        ) {
            $trigger = $activeAutomationTriggers[$triggerType];
            $action = $activeAutomationActions[$actionType];
            $trigger::getPostPatchSchema($schema);
            $action::getPostPatchSchema($schema);
            if ($type === "post") {
                $schema->addValidator("", [$this->automationRuleModel, "validateRecipe"]);
            }
        } else {
            $schema
                ->addValidator("trigger.triggerType", function (string $triggerType, ValidationField $field) use (
                    $activeAutomationTriggers
                ) {
                    if (empty($activeAutomationTriggers[$triggerType])) {
                        $field->addError("Invalid trigger type.", ["code" => 400]);
                        return false;
                    }
                    return $activeAutomationTriggers[$triggerType];
                })
                ->addValidator("action.actionType", function (string $actionType, ValidationField $field) use (
                    $activeAutomationActions
                ) {
                    if (empty($activeAutomationActions[$actionType])) {
                        $field->addError("Invalid action type.", ["code" => 400]);
                        return false;
                    }
                    return $activeAutomationActions[$actionType];
                });
        }

        return $schema;
    }

    /**
     * Update the status of an automation recipe
     *
     * @param int $id
     * @param array $body
     * @return Data
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws PermissionException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    public function put_status(int $id, array $body): Data
    {
        $this->permission("Garden.Settings.Manage");
        $this->idParamSchema();
        $in = $this->schema(["status:s" => "The status of the recipe."]);
        $body = $in->validate($body);
        $this->automationRuleModel->updateAutomationRuleStatus($id, $body["status"]);
        $out = $this->schema($this->automationRuleModel->getAutomationRuleSchema(), "out");
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($id);
        return new Data($out->validate($automationRule));
    }

    /**
     * Inbound schema for API call to
     * - `/automation-rules/{automationRuleID}/dispatches`
     * - `/automation-rules/{automationRuleID}/dispatches/{ruleDispatchID}`
     * - `/automation-rules/dispatches`
     *
     * @return Schema
     */
    private function getDispatchSchema(): Schema
    {
        return $this->schema([
            "automationRuleID:i?" => "Filter by the automation Rule ID.",
            "automationRuleDispatchUUID:s?" => "Filter by automationRuleDispatchUUID.",
            "actionType:s?" => "Filter by actionType.",
            "dispatchStatus:a?" => [
                "description" => "Filter by automation rule dispatch status.",
                "items" => [
                    "type" => "string",
                    "enum" => AutomationRuleDispatchesModel::STATUS_OPTIONS,
                ],
            ],
            "dateUpdated?" => new DateFilterSchema([
                "description" => "The date the automationRule was updated.",
                "x-filter" => [
                    "field" => "ar.dateUpdated",
                    "processor" => [DateFilterSchema::class, "dateFilterField"],
                ],
            ]),
            "dateLastRun?" => new DateFilterSchema([
                "description" => "The date the automationRule was updated.",
                "x-filter" => [
                    "field" => "ar.dateLastRun",
                    "processor" => [DateFilterSchema::class, "dateFilterField"],
                ],
            ]),
            "dateFinished?" => new DateFilterSchema([
                "description" => "The date the automationRule finished running.",
                "x-filter" => [
                    "field" => "ard.dateFinished",
                    "processor" => [DateFilterSchema::class, "dateFilterField"],
                ],
            ]),
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => 30,
                "minimum" => 1,
                "maximum" => 100,
            ],
            "sort:a?" => [
                "description" => "The results' sort order.",
                "items" => [
                    "type" => "string",
                ],
            ],
        ]);
    }

    /**
     * Get automation dispatches
     *
     * @param int|null $automationRuleID
     * @param null $ruleDispatchID
     * @param array $query
     * @return Data
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws PermissionException
     * @throws ValidationException
     * @throws \Garden\Container\NotFoundException
     */
    public function get_dispatches(int $automationRuleID = null, $ruleDispatchID = null, array $query = []): Data
    {
        $this->permission("Garden.Settings.Manage");
        $in = $this->getDispatchSchema();
        // Validate inbound data through schema.
        $query = $in->validate($query);
        // Convert date fields to where criteria using schema validation.
        $refinedWhere = ApiUtils::queryToFilters($in, $query);
        unset($query["dateUpdated"], $query["dateLastRun"]);

        // Convert $query into $requestQuery
        if (!is_null($automationRuleID)) {
            // Check if the Automation Rule exists.
            $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
            $query = array_merge($query, [
                "automationRuleID" => $automationRuleID,
            ]);
        }

        if (!is_null($ruleDispatchID)) {
            $query["automationRuleDispatchesUUID"] = $ruleDispatchID;
        }

        // Count total records with current query.
        $totalCount = $this->automationRuleDispatchesModel->getCountAutomationRuleDispatches($query, $refinedWhere);

        // Fetch the results, limits & paginates as well as do the appropriate expands.
        $results = $this->automationRuleDispatchesModel->getAutomationRuleDispatches($query, $refinedWhere);

        // Expand on the dispatch user data by default.
        \Gdn::userModel()->expandUsers($results, ["dispatchUserID"]);

        $out = $this->schema(
            [
                ":a" => $this->automationRuleDispatchesModel->getDispatchSchema(),
            ],
            "out"
        );
        $results = $out->validate($results);

        $paging = ApiUtils::numberedPagerInfo($totalCount, "/api/v2/automation-rules/dispatches", $query, $in);
        return new Data($results, [
            "paging" => $paging,
            APIExpandMiddleware::META_EXPAND_PREFIXES => ["automationRule"],
        ]);
    }

    /**
     * Run selected automation rule with Long Runner Trigger.
     *
     * @param int $id
     * @return Data
     * @throws ContainerException
     * @throws HttpException
     * @throws NoResultsException
     * @throws PermissionException
     * @throws NotFoundException
     */
    public function post_trigger(int $id): Data
    {
        $this->permission("Garden.Settings.Manage");

        $automationRule = $this->automationRuleModel->getAutomationRuleByID($id);
        if ($automationRule["status"] === AutomationRuleModel::STATUS_DELETED) {
            throw new NotFoundException("Automation rule not found.");
        }
        $actionClass = $this->automationRuleService->getAction($automationRule["action"]["actionType"]);
        if (!$actionClass) {
            throw new NotFoundException("Action is not registered.");
        }
        $trigger = $this->automationRuleService->getAutomationTrigger($automationRule["trigger"]["triggerType"]);
        if (!$trigger) {
            throw new NotFoundException("Trigger is not registered.");
        }

        $action = new $actionClass($id, AutomationRuleDispatchesModel::TYPE_MANUAL);
        $result = $action->triggerLongRunnerRule($trigger, true);

        return new Data($result);
    }
}
