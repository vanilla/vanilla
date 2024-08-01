<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\AutomationRules\APIv2;

use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Garden\Container\ContainerException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\AddonManager;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Exception\Database\NoResultsException;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\AutomationRules\AutomationRulesTestTrait;
use VanillaTests\AutomationRules\Models\MockAutomationRuleModel;
use VanillaTests\AutomationRules\ProfileFieldTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Automation rule controller test
 */
class AutomationRulesTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait,
        ExpectExceptionTrait,
        CommunityApiTestTrait,
        ProfileFieldTrait,
        AutomationRulesTestTrait;

    private AutomationRuleService $automationRuleService;
    private AddonManager $addonManager;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        \Gdn::config()->set("Feature.CommunityManagementBeta.Enabled", true);
        \Gdn::config()->set("Feature.escalations.Enabled", true);

        $this->automationRuleService = $this->container()->get(AutomationRuleService::class);
        $this->automationRuleModel = $this->container()->get(AutomationRuleModel::class);
        $this->automationRuleDispatchesModel = $this->container()->get(AutomationRuleDispatchesModel::class);
        $this->addonManager = $this->container()->get(AddonManager::class);
        $this->createUserFixtures();
        $mockAutomationRuleModel = $this->container()->get(MockAutomationRuleModel::class);
        $this->container()->setInstance(AutomationRuleModel::class, $mockAutomationRuleModel);
    }

    /**
     * Test that users without Garden.Settings.Manage is thrown a permission error
     *
     * @return void
     */
    public function testCatalogThrowsPermissionError(): void
    {
        $this->runWithUser(function () {
            $this->expectExceptionMessage("Permission Problem");
            $this->expectException(ForbiddenException::class);
            $this->api()->get("automation-rules/catalog");
        }, $this->memberID);
    }

    /**
     * Test that users with Garden.Settings.Manage can access the catalog and get a valid schema
     *
     * @return void
     */
    public function testCatalogGivesBackValidSchema(): void
    {
        $this->runWithUser(function () {
            $response = $this->api()
                ->get("automation-rules/catalog")
                ->getBody();
            $this->assertIsArray($response);
            $this->assertArrayHasKey("triggers", $response);
            $this->assertArrayHasKey("actions", $response);
            $this->assertIsArray($response["triggers"]);
            $this->assertIsArray($response["actions"]);
            $this->assertEquals($this->getExpectedCatalogTriggerArray(), $response["triggers"]);
            $this->assertEquals($this->getExpectedCatalogActionArray(), $response["actions"]);
        }, $this->adminID);
    }

    /**
     * Test that catalog only provides escalation triggers and actions when the escalation parameter is set to true
     *
     * @return void
     */
    public function testCatalogGivesBackOnlyEscalationTriggersAndActions(): void
    {
        $this->runWithUser(function () {
            $response = $this->api()
                ->get("automation-rules/catalog?escalations=true")
                ->getBody();
            $this->assertIsArray($response["triggers"]);
            $this->assertIsArray($response["actions"]);
            $this->assertEquals($this->getExpectedCatalogEscalationTriggerArray(), $response["triggers"]);
            $this->assertEquals($this->getExpectedCatalogEscalationActionArray(), $response["actions"]);
        }, $this->adminID);
    }

    /**
     * Expected schema array for triggers
     *
     * @return array[]
     */
    private function getExpectedCatalogTriggerArray(): array
    {
        $triggerTimeDelaySchema = [
            "type" => "object",
            "x-control" => [
                "description" => "Set the duration after which the rule will trigger.  Whole numbers only.",
                "label" => "Trigger Delay",
                "inputType" => "timeDuration",
                "placeholder" => "",
                "tooltip" =>
                    "Set the duration something needs to exist and meet the rule criteria for prior to the the rule triggering and acting upon it",
                "supportedUnits" => ["hour", "day", "week", "year"],
            ],
            "properties" => [
                "length" => [
                    "type" => "string",
                ],
                "unit" => [
                    "type" => "string",
                ],
            ],
            "required" => true,
        ];

        $triggerAdditionalSettingsSchema = [
            "applyToNewContentOnly" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => [
                    "description" =>
                        "When enabled, this rule will only be applied to new content that meets the trigger criteria.",
                    "label" => "Apply to new content only",
                    "inputType" => "checkBox",
                    "labelType" => "none",
                ],
            ],
            "triggerTimeLookBackLimit" => [
                "type" => "object",
                "x-control" => [
                    "description" => "Do not apply the rule to content that is older than this.",
                    "label" => "Look-back Limit",
                    "inputType" => "timeDuration",
                    "placeholder" => "",
                    "tooltip" => "",
                    "supportedUnits" => ["hour", "day", "week", "year"],
                    "conditions" => [
                        [
                            "field" => "additionalSettings.triggerValue.applyToNewContentOnly",
                            "type" => "boolean",
                            "const" => false,
                        ],
                    ],
                ],
                "properties" => [
                    "length" => [
                        "type" => "string",
                    ],
                    "unit" => [
                        "type" => "string",
                    ],
                ],
            ],
        ];

        $timeTriggerSchema = [
            "type" => "object",
            "properties" => [
                "triggerTimeDelay" => $triggerTimeDelaySchema,
                "postType" => [
                    "type" => "array",
                    "items" => [
                        "type" => "string",
                    ],
                    "default" => ["discussion"],
                    "enum" => ["discussion"],
                    "x-control" => [
                        "description" => "",
                        "label" => "Post Type",
                        "inputType" => "dropDown",
                        "placeholder" => "",
                        "choices" => [
                            "staticOptions" => [
                                "discussion" => "Discussion",
                            ],
                        ],
                        "multiple" => true,
                        "tooltip" => "",
                    ],
                    "required" => true,
                ],
                "additionalSettings" => $triggerAdditionalSettingsSchema,
            ],
            "required" => ["triggerTimeDelay", "postType", "additionalSettings"],
        ];
        $triggers = [
            "emailDomainTrigger" => [
                "triggerType" => "emailDomainTrigger",
                "name" => "New/Updated Email domain",
                "triggerActions" => ["categoryFollowAction", "addRemoveRoleAction"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "emailDomain" => [
                            "type" => "string",
                            "x-control" => [
                                "description" => "Enter one or more comma-separated email domains",
                                "label" => "Email Domain",
                                "inputType" => "textBox",
                                "placeholder" => "",
                                "type" => "string",
                                "tooltip" => "",
                            ],
                            "required" => true,
                        ],
                    ],
                    "required" => ["emailDomain"],
                ],
                "contentType" => "users",
            ],
            "profileFieldTrigger" => [
                "triggerType" => "profileFieldTrigger",
                "name" => "New/Updated Profile field",
                "triggerActions" => ["categoryFollowAction", "addRemoveRoleAction"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "profileField" => [
                            "type" => "string",
                            "x-control" => [
                                "description" =>
                                    "Dropdown (Single-, Multi-, or Numeric) and Single Checkbox profile field types are eligible for automation.",
                                "label" => "Profile Field",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "api" => [
                                        "searchUrl" =>
                                            "/api/v2/profile-fields?enabled=true&formType[]=dropdown&formType[]=tokens&formType[]=checkbox",
                                        "singleUrl" => "/api/v2/profile-fields/%s",
                                        "valueKey" => "apiName",
                                        "labelKey" => "label",
                                        "extraLabelKey" => null,
                                    ],
                                ],
                                "multiple" => false,
                                "tooltip" => "",
                            ],
                            "required" => true,
                        ],
                    ],
                    "required" => ["profileField"],
                ],
                "contentType" => "users",
            ],
            "staleDiscussionTrigger" => [
                "triggerType" => "staleDiscussionTrigger",
                "name" => "Time since a post has no comments",
                "triggerActions" => [
                    "addToCollectionAction",
                    "addTagAction",
                    "bumpDiscussionAction",
                    "closeDiscussionAction",
                    "moveToCategoryAction",
                    "removeDiscussionFromCollectionAction",
                ],
                "schema" => $timeTriggerSchema,
                "contentType" => "posts",
            ],
            "staleCollectionTrigger" => [
                "triggerType" => "staleCollectionTrigger",
                "name" => "Time since added to collection",
                "triggerActions" => ["removeDiscussionFromTriggerCollectionAction"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "triggerTimeDelay" => $triggerTimeDelaySchema,
                        "collectionID" => self::getCollectionSchema("Collection to remove from")["properties"][
                            "collectionID"
                        ],
                        "additionalSettings" => $triggerAdditionalSettingsSchema,
                    ],
                    "required" => ["triggerTimeDelay", "collectionID", "additionalSettings"],
                ],
                "contentType" => "posts",
            ],
            "lastActiveDiscussionTrigger" => [
                "triggerType" => "lastActiveDiscussionTrigger",
                "name" => "Time since post had no activity",
                "triggerActions" => [
                    "addToCollectionAction",
                    "addTagAction",
                    "bumpDiscussionAction",
                    "closeDiscussionAction",
                    "moveToCategoryAction",
                    "removeDiscussionFromCollectionAction",
                ],
                "schema" => $timeTriggerSchema,
                "contentType" => "posts",
            ],
            "timeSinceUserRegistrationTrigger" => [
                "triggerType" => "timeSinceUserRegistrationTrigger",
                "name" => "Time since Registration",
                "triggerActions" => ["categoryFollowAction", "addRemoveRoleAction"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "triggerTimeDelay" => $triggerTimeDelaySchema,
                        "additionalSettings" => $triggerAdditionalSettingsSchema,
                    ],
                    "required" => ["triggerTimeDelay", "additionalSettings"],
                ],
                "contentType" => "users",
            ],
            "reportPostTrigger" => [
                "triggerType" => "reportPostTrigger",
                "name" => "Post received reports",
                "triggerActions" => ["createEscalationAction"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "countReports" => [
                            "type" => "integer",
                            "required" => true,
                            "x-control" => [
                                "description" => "The number of reports received on a post",
                                "label" => "Number of Reports",
                                "inputType" => "textBox",
                                "placeholder" => "",
                                "type" => "integer",
                                "tooltip" => "",
                            ],
                        ],
                        "categoryID" => [
                            "type" => "array",
                            "items" => [
                                "type" => "string",
                            ],
                            "x-control" => [
                                "description" => "Select category",
                                "label" => "Category",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "api" => [
                                        "searchUrl" => "/api/v2/categories",
                                        "singleUrl" => "/api/v2/categories/%s",
                                        "valueKey" => "categoryID",
                                        "labelKey" => "name",
                                        "extraLabelKey" => null,
                                    ],
                                ],
                                "multiple" => true,
                                "tooltip" => "",
                            ],
                        ],
                        "includeSubcategories" => [
                            "type" => "boolean",
                            "x-control" => [
                                "description" => "Include content from subcategories of the chosen category",
                                "label" => "Include Subcategories",
                                "inputType" => "checkBox",
                                "labelType" => null,
                            ],
                        ],
                        "reportReasonID" => [
                            "type" => "array",
                            "items" => [
                                "type" => "string",
                            ],
                            "default" => [],
                            "enum" => [
                                "abuse",
                                "approval-required",
                                "deceptive-misleading",
                                "inappropriate",
                                "rule-breaking",
                                "spam",
                                "spam-automation",
                            ],
                            "x-control" => [
                                "description" => "",
                                "label" => "Report Reason",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "staticOptions" => [
                                        "spam" => "Spam / Solicitation",
                                        "approval-required" => "Approval Required",
                                        "deceptive-misleading" => "Deceptive / Misleading",
                                        "inappropriate" => "Inappropriate",
                                        "rule-breaking" => "Breaks Community Rules",
                                        "spam-automation" => "Spam Automation",
                                        "abuse" => "Abuse",
                                    ],
                                ],
                                "multiple" => true,
                                "tooltip" => "",
                            ],
                        ],
                    ],
                    "required" => ["countReports"],
                ],
                "contentType" => "posts",
            ],
        ];
        return $triggers;
    }

    /**
     * Helper for expected collections schema
     * @param string $label
     * @return array[]
     */
    private function getCollectionSchema(string $label): array
    {
        return [
            "type" => "object",
            "properties" => [
                "collectionID" => [
                    "type" => "array",
                    "items" => [
                        "type" => "integer",
                    ],
                    "x-control" => [
                        "description" => "Select one or more collections.",
                        "label" => $label,
                        "inputType" => "dropDown",
                        "placeholder" => "",
                        "choices" => [
                            "api" => [
                                "searchUrl" => "/api/v2/collections",
                                "singleUrl" => "/api/v2/collections/%s",
                                "valueKey" => "collectionID",
                                "labelKey" => "name",
                                "extraLabelKey" => null,
                            ],
                        ],
                        "multiple" => true,
                        "tooltip" => "",
                    ],
                    "required" => true,
                ],
            ],
            "required" => ["collectionID"],
        ];
    }

    /**
     * Helper for expected categories schema
     * @param string $label
     * @param string $description
     * @param bool $multiple
     * @return array[]
     */
    private function getCategorySchema(string $label, string $description, bool $multiple = false): array
    {
        return [
            "type" => "object",
            "properties" => [
                "categoryID" => [
                    "type" => "array",
                    "items" => [
                        "type" => "integer",
                    ],
                    "required" => true,
                    "x-control" => [
                        "description" => $description,
                        "label" => $label,
                        "inputType" => "dropDown",
                        "placeholder" => "",
                        "choices" => [
                            "api" => [
                                "searchUrl" => "/api/v2/categories/search?query=%s&limit=30&displayAs[]=Discussions",
                                "singleUrl" => "/api/v2/categories/%s",
                                "valueKey" => "categoryID",
                                "labelKey" => "name",
                                "extraLabelKey" => null,
                            ],
                        ],
                        "multiple" => $multiple,
                        "tooltip" => "",
                    ],
                ],
            ],
            "required" => ["categoryID"],
        ];
    }

    /**
     * Expected schema array for actions
     *
     * @return array[]
     */
    private function getExpectedCatalogActionArray(): array
    {
        $roleIDs = $this->getModerationManagePermissionRoleIDs();
        $qs = "?" . http_build_query(["roleIDs" => $roleIDs]);

        return [
            "categoryFollowAction" => [
                "actionType" => "categoryFollowAction",
                "name" => "Follow category",
                "actionTriggers" => ["emailDomainTrigger", "profileFieldTrigger", "timeSinceUserRegistrationTrigger"],
                "schema" => $this->getCategorySchema(
                    "Category to Follow",
                    "Select one or more categories to follow",
                    true
                ),
                "contentType" => "users",
            ],
            "moveToCategoryAction" => [
                "actionType" => "moveToCategoryAction",
                "name" => "Move post",
                "actionTriggers" => ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
                "schema" => $this->getCategorySchema(
                    "Category to move to",
                    "Category settings are respected by automation rules. Posts will only be moved into categories that accept that post type."
                ),
                "contentType" => "posts",
            ],
            "closeDiscussionAction" => [
                "actionType" => "closeDiscussionAction",
                "name" => "Close post",
                "actionTriggers" => ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
                "contentType" => "posts",
            ],
            "bumpDiscussionAction" => [
                "actionType" => "bumpDiscussionAction",
                "name" => "Bump post",
                "actionTriggers" => ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
                "contentType" => "posts",
            ],
            "addTagAction" => [
                "actionType" => "addTagAction",
                "name" => "Add tag",
                "actionTriggers" => ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "tagID" => [
                            "type" => "array",
                            "items" => [
                                "type" => "integer",
                            ],
                            "x-control" => [
                                "description" => "Select one or more tags",
                                "label" => "Tags to add",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "api" => [
                                        "searchUrl" => "/api/v2/tags?type=User&limit=30&query=%s",
                                        "singleUrl" => "/api/v2/tags/%s",
                                        "valueKey" => "tagID",
                                        "labelKey" => "name",
                                        "extraLabelKey" => null,
                                    ],
                                ],
                                "multiple" => true,
                                "tooltip" => "",
                            ],
                            "required" => true,
                        ],
                    ],
                    "required" => ["tagID"],
                ],
                "contentType" => "posts",
            ],
            "addToCollectionAction" => [
                "actionType" => "addToCollectionAction",
                "name" => "Add to collection",
                "actionTriggers" => ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
                "schema" => $this->getCollectionSchema("Collection to add to"),
                "contentType" => "posts",
            ],
            "removeDiscussionFromCollectionAction" => [
                "actionType" => "removeDiscussionFromCollectionAction",
                "name" => "Remove from collection",
                "actionTriggers" => ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"],
                "schema" => $this->getCollectionSchema("Collection to remove from"),
                "contentType" => "posts",
            ],
            "addRemoveRoleAction" => [
                "actionType" => "addRemoveRoleAction",
                "name" => "Assign/Remove role",
                "actionTriggers" => ["emailDomainTrigger", "profileFieldTrigger", "timeSinceUserRegistrationTrigger"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "addRoleID" => [
                            "type" => "string",
                            "x-control" => [
                                "description" => "",
                                "label" => "Assign Role",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "api" => [
                                        "searchUrl" => "/api/v2/roles",
                                        "singleUrl" => "/api/v2/roles/%s",
                                        "valueKey" => "roleID",
                                        "labelKey" => "name",
                                        "extraLabelKey" => null,
                                    ],
                                ],
                                "multiple" => false,
                                "tooltip" => "",
                            ],
                            "required" => true,
                        ],
                        "removeRoleID" => [
                            "type" => "string",
                            "x-control" => [
                                "description" => "",
                                "label" => "Remove Role (optional)",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "api" => [
                                        "searchUrl" => "/api/v2/roles",
                                        "singleUrl" => "/api/v2/roles/%s",
                                        "valueKey" => "roleID",
                                        "labelKey" => "name",
                                        "extraLabelKey" => null,
                                    ],
                                ],
                                "multiple" => false,
                                "tooltip" => "",
                            ],
                        ],
                    ],
                    "required" => ["addRoleID"],
                ],
                "contentType" => "users",
            ],
            "removeDiscussionFromTriggerCollectionAction" => [
                "actionType" => "removeDiscussionFromTriggerCollectionAction",
                "name" => "Remove from trigger collection",
                "actionTriggers" => ["staleCollectionTrigger"],
                "contentType" => "posts",
            ],
            "createEscalationAction" => [
                "actionType" => "createEscalationAction",
                "name" => "Create Escalation",
                "actionTriggers" => ["reportPostTrigger"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "recordIsLive" => [
                            "type" => "boolean",
                            "default" => false,
                            "x-control" => [
                                "description" => "Keep post visible in community",
                                "label" => "Keep record live",
                                "inputType" => "checkBox",
                                "labelType" => null,
                            ],
                        ],
                        "assignedModeratorID" => [
                            "type" => "integer",
                            "x-control" => [
                                "description" => "Select what moderator escalations should be assigned to",
                                "label" => "Assign Moderator",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "api" => [
                                        "searchUrl" => "/api/v2/users$qs",
                                        "singleUrl" => "/api/v2/users/%s",
                                        "valueKey" => "userID",
                                        "labelKey" => "name",
                                        "extraLabelKey" => null,
                                    ],
                                ],
                                "multiple" => false,
                                "tooltip" => "",
                            ],
                        ],
                    ],
                ],
                "contentType" => "posts",
            ],
        ];
    }

    /**
     * Get moderation manage permission role IDs
     *
     * @return array
     */
    private function getModerationManagePermissionRoleIDs(): array
    {
        $roles = $this->roleModel->getByPermission("Garden.Moderation.Manage")->resultArray();
        return array_column($roles, "RoleID");
    }

    /**
     * Expected escalation triggers
     * @return array[]
     */
    private function getExpectedCatalogEscalationTriggerArray(): array
    {
        $triggers = ["reportPostTrigger", "staleDiscussionTrigger", "lastActiveDiscussionTrigger"];
        return array_intersect_key($this->getExpectedCatalogTriggerArray(), array_flip($triggers));
    }

    private function getExpectedCatalogEscalationActionArray(): array
    {
        $roleIDs = $this->getModerationManagePermissionRoleIDs();
        $qs = "?" . http_build_query(["roleIDs" => $roleIDs]);
        return [
            "createEscalationAction" => [
                "actionType" => "createEscalationAction",
                "name" => "Create Escalation",
                "actionTriggers" => ["reportPostTrigger"],
                "schema" => [
                    "type" => "object",
                    "properties" => [
                        "recordIsLive" => [
                            "type" => "boolean",
                            "default" => false,
                            "x-control" => [
                                "description" => "Keep post visible in community",
                                "label" => "Keep record live",
                                "inputType" => "checkBox",
                                "labelType" => null,
                            ],
                        ],
                        "assignedModeratorID" => [
                            "type" => "integer",
                            "x-control" => [
                                "description" => "Select what moderator escalations should be assigned to",
                                "label" => "Assign Moderator",
                                "inputType" => "dropDown",
                                "placeholder" => "",
                                "choices" => [
                                    "api" => [
                                        "searchUrl" => "/api/v2/users$qs",
                                        "singleUrl" => "/api/v2/users/%s",
                                        "valueKey" => "userID",
                                        "labelKey" => "name",
                                        "extraLabelKey" => null,
                                    ],
                                ],
                                "multiple" => false,
                                "tooltip" => "",
                            ],
                        ],
                    ],
                ],
                "contentType" => "posts",
            ],
        ];
    }

    /**
     * Test that users without Garden.Settings.Manage is thrown a permission error when trying to add a new recipe
     */
    public function testPostThrowsPermissionError(): void
    {
        $this->runWithUser(function () {
            $this->expectExceptionMessage("Permission Problem");
            $this->expectException(ForbiddenException::class);
            $this->api()->post("automation-rules", ["trigger" => [], "action" => []]);
        }, $this->memberID);
    }

    /**
     * Test automation rule post call validation
     *
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider getPostTriggerActionDataProvider
     */
    public function testPostTriggerActionValidation(array $body, string $errorMessage): void
    {
        $this->runWithExpectedExceptionMessage($errorMessage, function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
    }

    /**
     * provide test data for testPostTriggerActionValidation()
     *
     * @return array[]
     */
    public function getPostTriggerActionDataProvider(): array
    {
        return [
            "test empty body" => [[], "Trigger and action are required"],
            "test empty trigger and action" => [["trigger" => [], "action" => []], "Trigger and action are required"],
            "test name is required" => [
                [
                    "trigger" => ["triggerType" => "testTrigger", "triggerValue" => ["test" => "testValue"]],
                    "action" => ["actionType" => "testAction", "actionValue" => []],
                ],
                "name is required.",
            ],
            "test trigger type is required" => [
                [
                    "name" => "testRecipe",
                    "trigger" => ["name" => "testTrigger", "triggerValue" => ["test" => "testValue"]],
                    "action" => ["actionType" => "testAction", "actionValue" => []],
                ],
                "trigger.triggerType is required.",
            ],
            "test action type is required" => [
                [
                    "name" => "testRecipe",
                    "trigger" => [
                        "triggerType" => "emailDomainTrigger",
                        "triggerValue" => ["emailDomain" => "google.com"],
                    ],
                    "action" => ["name" => "testAction", "actionValue" => []],
                ],
                "action.actionType is required.",
            ],
            "test invalid trigger type" => [
                [
                    "name" => "testRecipe",
                    "trigger" => ["triggerType" => "invalidTrigger", "triggerValue" => []],
                    "action" => ["actionType" => "categoryFollowAction", "actionValue" => ["categoryID" => [23]]],
                ],
                "Invalid trigger type.",
            ],
            "test invalid action type" => [
                [
                    "name" => "testRecipe",
                    "trigger" => ["triggerType" => "emailDomainTrigger", "triggerValue" => [""]],
                    "action" => ["actionType" => "InvalidAction", "actionValue" => ["categoryID" => [23]]],
                ],
                "Invalid action type.",
            ],
            "test invalid action type validation" => [
                [
                    "name" => "testRecipe",
                    "trigger" => [
                        "triggerType" => "emailDomainTrigger",
                        "triggerValue" => ["emailDomain" => "google.com"],
                    ],
                    "action" => [
                        "actionType" => BumpDiscussionAction::getType(),
                        "actionValue" => ["categoryID" => [23]],
                    ],
                ],
                BumpDiscussionAction::getType() . " is not a valid action type.",
            ],
        ];
    }

    /**
     * Test email domain trigger validation
     *
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider emailDomainTriggerDataProvider
     */
    public function testEmailDomainTriggerValidation(array $body, string $errorMessage): void
    {
        $this->runWithExpectedExceptionMessage($errorMessage, function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
    }

    /**
     * Data provider for testEmailDomainTriggerValidation
     *
     * @return array[]
     */
    public function emailDomainTriggerDataProvider(): array
    {
        $body = [
            "name" => "testRecipe",
            "trigger" => ["triggerType" => "emailDomainTrigger", "triggerValue" => ""],
            "action" => ["actionType" => "categoryFollowAction", "actionValue" => ["categoryID" => ["25"]]],
        ];
        return [
            "test empty emailDomain" => [
                $this->modifyRecord($body, ["triggerValue" => ["testKey" => "testValue"]]),
                "trigger.triggerValue.emailDomain is required.",
            ],
            "test empty emailDomain value" => [
                $this->modifyRecord($body, ["triggerValue" => ["emailDomain" => ""]]),
                "You should provide at least one email domain.",
            ],
            "test invalid emailDomain value" => [
                $this->modifyRecord($body, ["triggerValue" => ["emailDomain" => "test.co.eu"]]),
                "Could not resolve domain test.co.eu.",
            ],
            "test with multiple emailDomain value" => [
                $this->modifyRecord($body, ["triggerValue" => ["emailDomain" => "yahoo.com, invalid.com"]]),
                "Could not resolve domain invalid.com.",
            ],
            "test with integer value" => [
                $this->modifyRecord($body, ["triggerValue" => ["emailDomain" => "123"]]),
                "Could not resolve domain 123.",
            ],
            "test with ip address" => [
                $this->modifyRecord($body, ["triggerValue" => ["emailDomain" => "123.0.0.1"]]),
                "You should provide a domain name, not an IP address.",
            ],
        ];
    }

    /**
     * Replace a record with the given replacements
     *
     * @param array $record
     * @param array|null $replacements
     * @return array[]
     */
    private function modifyRecord(array $record, ?array $replacements): array
    {
        if (!empty($replacements)) {
            array_walk_recursive($record, function (&$value, $key) use ($replacements) {
                if (array_key_exists($key, $replacements)) {
                    $value = $replacements[$key];
                }
            });
        }

        return $record;
    }

    /**
     * Test profile field trigger validation
     *
     * @return void
     */
    public function testProfileFieldTriggerValidation(): void
    {
        // Test without passing profileField key for value throws validation error
        $body = [
            "name" => "testRecipe",
            "trigger" => ["triggerType" => "profileFieldTrigger", "triggerValue" => ["testKey" => "testValue"]],
            "action" => ["actionType" => "categoryFollowAction", "actionValue" => ["categoryID" => [25]]],
        ];
        $this->runWithExpectedExceptionMessage("trigger.triggerValue.profileField is required.", function () use (
            $body
        ) {
            $this->api()->post("automation-rules", $body);
        });

        // Test providing empty profileField throws validation error
        $body["trigger"]["triggerValue"] = ["profileField" => []];
        $this->runWithExpectedExceptionMessage("Profile field is required.", function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });

        // Test providing invalid  profileField throws validation error
        $body["trigger"]["triggerValue"] = ["profileField" => ["testKey" => "testValue"]];

        $this->runWithExpectedExceptionMessage("testKey doesn't exist.", function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
        $this->profileFieldCheckBoxValidation();
        $this->profileFieldDropDownValidation();
    }

    /**
     * Checkbox type profile field validation
     *
     * @return void
     */
    private function profileFieldCheckBoxValidation(): void
    {
        $profileField = $this->generateCheckboxField([
            "apiName" => "subscribe",
            "label" => "Subscribe",
        ]);
        $body = [
            "name" => "testRecipe",
            "trigger" => [
                "triggerType" => "profileFieldTrigger",
                "triggerValue" => ["profileField" => ["subscribe" => "abc"]],
            ],
            "action" => ["actionType" => "categoryFollowAction", "actionValue" => ["categoryID" => [25]]],
        ];
        $this->runWithExpectedExceptionMessage("subscribe is not a valid boolean.", function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
    }

    /**
     * Dropdown type profile field validation
     *
     * @return void
     */
    private function profileFieldDropDownValidation(): void
    {
        $this->generateDropDownField([
            "apiName" => "interests",
            "label" => "Interests",
            "dropdownOptions" => ["option1", "option2", "option3"],
        ]);

        $body = [
            "name" => "testRecipe",
            "trigger" => [
                "triggerType" => "profileFieldTrigger",
                "triggerValue" => ["profileField" => ["interests" => ["option4", "option2"]]],
            ],
            "action" => ["actionType" => "categoryFollowAction", "actionValue" => ["categoryID" => [1]]],
        ];
        $this->runWithExpectedExceptionMessage(
            "interests[0] must be one of: option1, option2, option3.",
            function () use ($body) {
                $this->api()->post("automation-rules", $body);
            }
        );

        $body["trigger"]["triggerValue"]["profileField"]["interests"] = [];
        $this->runWithExpectedExceptionMessage(
            "trigger.triggerValue.interests must contain at least 1 item.",
            function () use ($body) {
                $this->api()->post("automation-rules", $body);
            }
        );
        //check for numeric token type
        $this->generateProfileField([
            "apiName" => "score",
            "label" => "Score",
            "formType" => "tokens",
            "dataType" => ProfileFieldModel::DATA_TYPE_NUMBER_MUL,
            "dropdownOptions" => [1, 2, 3],
        ]);
        $body["trigger"]["triggerValue"]["profileField"] = ["score" => ["1", "2", "5"]];
        $this->runWithExpectedExceptionMessage(
            "trigger.triggerValue.score[2] must be one of: 1, 2, 3",
            function () use ($body) {
                $this->api()->post("automation-rules", $body);
            }
        );
    }

    /**
     * Test category follow action validation
     *
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider categoryFollowDataProvider
     */
    public function testCategoryFollowActionValidation(array $body, string $errorMessage): void
    {
        $this->runWithExpectedExceptionMessage($errorMessage, function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
    }

    /**
     * Data provider for testCategoryFollowActionValidation
     *
     * @return array[]
     */
    public function categoryFollowDataProvider(): array
    {
        $body = [
            "name" => "testRecipe",
            "trigger" => ["triggerType" => "emailDomainTrigger", "triggerValue" => ["emailDomain" => "gmail.com"]],
            "action" => ["actionType" => "categoryFollowAction", "actionValue" => ""],
        ];
        return [
            "test required categoryID" => [
                $this->modifyRecord($body, ["actionValue" => ["testKey" => "testValue"]]),
                "action.actionValue.categoryID is required.",
            ],
            "test empty categoryID" => [
                $this->modifyRecord($body, ["actionValue" => ["categoryID" => []]]),
                "You should provide at least one category to follow.",
            ],
            "test CategoryId is a valid array with integer values" => [
                $this->modifyRecord($body, ["actionValue" => ["categoryID" => ["some string"]]]),
                "action.actionValue.categoryID[0] is not a valid integer.",
            ],
            "test invalid category" => [
                $this->modifyRecord($body, ["actionValue" => ["categoryID" => [25]]]),
                "The category 25 is not a valid category.",
            ],
        ];
    }

    /**
     * Test modify role action validation
     *
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider addRemoveRoleActionDataProvider
     */
    public function testAddRemoveRoleActionValidation(array $body, string $errorMessage): void
    {
        $this->runWithExpectedExceptionMessage($errorMessage, function () use ($body) {
            $this->api()->post("automation-rules", $body);
        });
    }

    /**
     * Data provide for testAddRemoveRoleActionValidation
     *
     * @return array
     */
    public function addRemoveRoleActionDataProvider(): array
    {
        $body = [
            "name" => "testRecipe",
            "trigger" => ["triggerType" => "emailDomainTrigger", "triggerValue" => ["emailDomain" => "google.com"]],
            "action" => ["actionType" => "addRemoveRoleAction", "actionValue" => ""],
        ];

        return [
            "test roleID is added" => [
                $this->modifyRecord($body, ["actionValue" => ["testKey" => "testValue"]]),
                "action.actionValue.addRoleID is required.",
            ],
            "test roleID provided is a valid integer" => [
                $this->modifyRecord($body, ["actionValue" => ["addRoleID" => "sdfsdf"]]),
                "action.actionValue.addRoleID is not a valid integer.",
            ],
            "test role exists" => [
                $this->modifyRecord($body, ["actionValue" => ["addRoleID" => 25]]),
                "Role provided to add doesn't exist.",
            ],
            "test same role ids for add and remove throws validation error" => [
                $this->modifyRecord($body, ["actionValue" => ["addRoleID" => 16, "removeRoleID" => 16]]),
                "Remove Role should not be same as Add Role.",
            ],
            "test roleID provided to remove exist" => [
                $this->modifyRecord($body, ["actionValue" => ["addRoleID" => 16, "removeRoleID" => 5]]),
                "Role provided to remove doesn't exist.",
            ],
        ];
    }

    /**
     * Test Stale Discussion Trigger validation
     *
     * @param array $body
     * @param string $errorMessage
     * @return void
     * @dataProvider staleDiscussionDataProvider
     */
    public function testStaleDiscussionTriggerValidation(array $body, string $errorMessage): void
    {
        if ($errorMessage == "") {
            $response = $this->api()->post("automation-rules", $body);
            $this->assertEquals(201, $response->getStatusCode());
        } else {
            $this->runWithExpectedExceptionMessage($errorMessage, function () use ($body) {
                $this->api()->post("automation-rules", $body);
            });
        }
    }

    /**
     * Data provider for testStaleDiscussionTriggerValidation
     *
     * @return array[]
     */
    public function staleDiscussionDataProvider(): array
    {
        $body = [
            "name" => "testRecipe",
            "trigger" => [
                "triggerType" => "staleDiscussionTrigger",
                "triggerValue" => [
                    "applyToNewContentOnly" => false,
                    "triggerTimeLookBackLimit" => "",
                    "triggerTimeDelay" => "",
                    "postType" => "",
                ],
            ],
            "action" => ["actionType" => "closeDiscussionAction", "actionValue" => []],
        ];

        $bodyTrigger = $body;
        $bodyTrigger["trigger"]["triggerValue"]["postType"] = ["discussion"];
        $bodyTrigger["action"]["actionValue"] = "";
        return [
            "test required duration" => [
                $this->modifyRecord($body, [
                    "triggerTimeDelay" => [
                        "length" => "",
                        "unit" => "week",
                    ],
                    "postType" => ["discussion"],
                ]),
                "trigger.triggerValue.triggerTimeDelay.length is not a valid integer.",
            ],
            "test required duration to be whole number" => [
                $this->modifyRecord($body, [
                    "triggerTimeDelay" => [
                        "length" => -5,
                        "unit" => "week",
                    ],
                    "postType" => ["discussion"],
                ]),
                "Trigger Delay should be positive whole numbers only.",
            ],
            "test postType is a valid array with values" => [
                $this->modifyRecord($body, ["postType" => ["test"]]),
                "Invalid post type, Valid post types are: [\"discussion\"]",
            ],
            "test invalid maxTimeThreshold" => [
                $this->modifyRecord($body, [
                    "triggerTimeLookBackLimit" => [
                        "length" => -1,
                        "unit" => "week",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 1,
                        "unit" => "week",
                    ],
                ]),
                "Look-back Limit should be positive whole numbers only.",
            ],
            "test invalid triggerTimeUnit" => [
                $this->modifyRecord($body, [
                    "triggerTimeDelay" => [
                        "length" => 1,
                        "unit" => "minute",
                    ],
                ]),
                "trigger.triggerValue.triggerTimeDelay.unit must be one of: hour, day, week, year.",
            ],
            "test invalid tags" => [
                $this->modifyRecord($bodyTrigger, ["actionType" => "addTagAction", "actionValue" => ["tagID" => [25]]]),
                "Not all tags are valid.",
            ],
            "test invalid collection" => [
                $this->modifyRecord($bodyTrigger, [
                    "actionType" => "addToCollectionAction",
                    "actionValue" => ["collectionID" => [25]],
                ]),
                "Not all collections are valid.",
            ],
            "test invalid remove collection" => [
                $this->modifyRecord($bodyTrigger, [
                    "actionType" => "removeDiscussionFromCollectionAction",
                    "actionValue" => ["collectionID" => [25]],
                ]),
                "Not all collections are valid.",
            ],
            "test invalid category" => [
                $this->modifyRecord($bodyTrigger, [
                    "actionType" => "moveToCategoryAction",
                    "actionValue" => ["categoryID" => 25],
                ]),
                "The category 25 is not a valid category.",
            ],
            "test success" => [
                $this->modifyRecord($bodyTrigger, [
                    "triggerTimeLookBackLimit" => [
                        "length" => 2,
                        "unit" => "week",
                    ],
                    "triggerTimeDelay" => [
                        "length" => 1,
                        "unit" => "hour",
                    ],
                    "actionValue" => [],
                ]),
                "",
            ],
        ];
    }

    /**
     * Test create a new automation recipe.
     *
     * @return int
     */
    public function testPost(): int
    {
        //Test permission error
        $this->runWithExpectedException(ForbiddenException::class, function () {
            $this->runWithUser(function () {
                $this->api()->post("automation-rules", ["trigger" => [], "action" => []]);
            }, $this->memberID);
        });

        $this->createCategory(["name" => "Email Domain"]);
        $categoryID[] = $this->lastInsertedCategoryID;
        $this->createCategory(["name" => "Fun Category"]);
        $categoryID[] = $this->lastInsertedCategoryID;
        $automationRule = [
            "name" => "testPostRecipe",
            "trigger" => [
                "triggerType" => "emailDomainTrigger",
                "triggerValue" => ["emailDomain" => "example.com"],
            ],
            "action" => [
                "actionType" => "categoryFollowAction",
                "actionValue" => ["categoryID" => $categoryID],
            ],
        ];
        $response = $this->api()->post("automation-rules", $automationRule);
        $this->assertEquals(201, $response->getStatusCode());
        $automationRecipe = $response->getBody();
        $this->assertIsArray($automationRecipe);

        $this->assertArrayHasKey("automationRuleID", $automationRecipe);
        $this->assertArrayHasKey("automationRuleRevisionID", $automationRecipe);
        $this->assertEquals($automationRule["trigger"]["triggerType"], $automationRecipe["trigger"]["triggerType"]);
        $this->assertEquals($automationRule["trigger"]["triggerValue"], $automationRecipe["trigger"]["triggerValue"]);
        $this->assertEquals($automationRule["action"]["actionType"], $automationRecipe["action"]["actionType"]);
        $this->assertEquals($automationRule["action"]["actionValue"], $automationRecipe["action"]["actionValue"]);
        $this->assertEquals("inactive", $automationRecipe["status"]);

        return $automationRecipe["automationRuleID"];
    }

    /**
     * Test that the creation of automation rule with same name throws validation error
     *
     * @return void
     * @depends testPost
     */
    public function testAutomationRuleValidationForName(int $automationRuleID): void
    {
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
        $automationRuleName = $automationRule["name"];
        $this->runWithExpectedExceptionMessage(
            "Rule name already exists. Enter a unique name to proceed.",
            function () use ($automationRuleName) {
                $this->api()->post("automation-rules", [
                    "name" => "$automationRuleName",
                    "trigger" => [
                        "triggerType" => "emailDomainTrigger",
                        "triggerValue" => ["emailDomain" => "example.com"],
                    ],
                    "action" => ["actionType" => "categoryFollowAction", "actionValue" => ["categoryID" => [1]]],
                ]);
            }
        );

        //This should execute without error

        $result = $this->api()->patch("automation-rules/$automationRuleID", [
            "name" => $automationRuleName,
            "trigger" => $automationRule["trigger"],
            "action" => $automationRule["action"],
        ]);
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test that when a recipe is not found, a not found exception is thrown.
     *
     * @return void
     */
    public function testGetRecipeThrowsNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Automation rule not found.");
        $this->api()->get("automation-rules/999/recipe");
    }

    /**
     * Test get a recipe
     *
     * @param int $automationRuleID
     * @return void
     * @depends testPost
     */
    public function testGetRecipe(int $automationRuleID): void
    {
        $response = $this->api()->get("automation-rules/{$automationRuleID}/recipe");
        $this->assertEquals(200, $response->getStatusCode());
        $automationRecipe = $response->getBody();
        $this->assertIsArray($automationRecipe);
        $this->assertArrayHasKey("automationRuleID", $automationRecipe);
        $this->assertEquals($automationRuleID, $automationRecipe["automationRuleID"]);
        $this->assertArrayHasKey("automationRuleRevisionID", $automationRecipe);
    }

    /**
     * Test get a recipe with expand
     *
     * @param int $automationRuleID
     * @return void
     * @depends testPost
     */
    public function testGetRecipeWithDispatchStatus(int $automationRuleID): void
    {
        $automationRule = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
        // Create some dummy dispatch entries
        CurrentTimeStamp::mockTime(strtotime("-1 day 5 seconds"));
        $options = [
            "automationRuleDispatchUUID" => "abcdef12345",
            "automationRuleID" => $automationRuleID,
            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
            "dispatchType" => AutomationRuleDispatchesModel::TYPE_TRIGGERED,
            "dateFinished" => date("Y-m-d H:i:s", strtotime("-1 day 10 seconds")),
            "status" => AutomationRuleDispatchesModel::STATUS_SUCCESS,
        ];
        $this->createAutomationDispatches($options, 1);
        $options = array_merge($options, [
            "automationRuleDispatchUUID" => "zyxwvu54321",
            "status" => AutomationRuleDispatchesModel::STATUS_QUEUED,
            "dateDispatched" => date("Y-m-d H:i:s"),
            "dateFinished" => null,
        ]);
        CurrentTimeStamp::clearMockTime();
        $this->createAutomationDispatches($options, 1);
        $response = $this->api()->get("automation-rules/{$automationRuleID}/recipe?expand=dispatchStatus");
        $this->assertEquals(200, $response->getStatusCode());
        $automationRecipe = $response->getBody();
        $this->assertIsArray($automationRecipe);
        $this->assertArrayHasKey("automationRuleID", $automationRecipe);
        $this->assertEquals($automationRuleID, $automationRecipe["automationRuleID"]);
        $this->assertArrayHasKey("automationRuleRevisionID", $automationRecipe);
        $this->assertArrayHasKey("recentDispatch", $automationRecipe);

        // test we get the most recent dispatch
        $this->assertEquals("zyxwvu54321", $automationRecipe["recentDispatch"]["automationRuleDispatchUUID"]);
        $this->assertEquals(
            AutomationRuleDispatchesModel::STATUS_QUEUED,
            $automationRecipe["recentDispatch"]["dispatchStatus"]
        );
    }

    /**
     * Test for exceptions while updating a recipe
     *
     * @return void
     */
    public function testPatchRecipeThrowsExceptions(): void
    {
        // Test permission error
        $this->runWithExpectedException(ForbiddenException::class, function () {
            $this->runWithUser(function () {
                $this->api()->patch("automation-rules/1", ["trigger" => [], "action" => []]);
            }, $this->memberID);
        });

        // Test not found

        $this->runWithExpectedExceptionMessage("Automation rule not found.", function () {
            $this->api()->patch("automation-rules/999", ["trigger" => [], "action" => []]);
        });
        $automationRule = [
            "name" => "testPatchRecipe",
            "trigger" => [
                "triggerType" => "emailDomainTrigger",
                "triggerValue" => ["emailDomain" => "example.com"],
            ],
            "action" => [
                "actionType" => "categoryFollowAction",
                "actionValue" => ["categoryID" => [1]],
            ],
        ];
        $response = $this->api()->post("automation-rules", $automationRule);
        $automationRecipe = $response->getBody();
        $this->assertIsArray($automationRecipe);
        $automationID = $automationRecipe["automationRuleID"];
        // now mark this recipe as deleted
        $this->automationRuleModel->deleteAutomationRule($automationRecipe["automationRuleID"]);
        $automationRule["trigger"]["triggerValue"]["emailDomain"] = "example.org";
        $this->runWithExpectedExceptionMessage("Automation rule not found.", function () use (
            $automationID,
            $automationRule
        ) {
            $this->api()->patch("automation-rules/$automationID", $automationRule);
        });
    }

    /**
     * test update a recipe
     *
     * @param int $automationRuleID
     * @return void
     * @depends testPost
     */
    public function testPatchRecipe(int $automationRuleID)
    {
        $automationRecipe = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);

        $automationRecipe["status"] = AutomationRuleModel::STATUS_ACTIVE;
        $automationRecipe["trigger"]["triggerValue"]["emailDomain"] = "example.org";
        $response = $this->api()->patch("automation-rules/{$automationRuleID}", $automationRecipe);
        $this->assertEquals(200, $response->getStatusCode());
        $automationRule = $response->getBody();
        $this->assertIsArray($automationRule);
        $this->assertEquals($automationRecipe["automationRuleID"], $automationRule["automationRuleID"]);
        $this->assertNotEquals(
            $automationRecipe["automationRuleRevisionID"],
            $automationRule["automationRuleRevisionID"]
        );
        $this->assertEquals(
            $automationRecipe["trigger"]["triggerValue"]["emailDomain"],
            $automationRule["trigger"]["triggerValue"]["emailDomain"]
        );
        $this->assertEquals($automationRecipe["status"], $automationRule["status"]);
    }

    /**
     * Test deleting a recipe
     *
     * @param int $automationRuleID
     * @return void
     * @depends testPost
     */
    public function testDeleteRecipe(int $automationRuleID): void
    {
        // Test permission error
        $this->runWithExpectedException(ForbiddenException::class, function () use ($automationRuleID) {
            $this->runWithUser(function () use ($automationRuleID) {
                $this->api()->delete("automation-rules/$automationRuleID");
            }, $this->memberID);
        });

        $response = $this->api()->delete("automation-rules/$automationRuleID");
        $this->assertEquals(204, $response->getStatusCode());

        $automationRule = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);
        $this->assertEquals(AutomationRuleModel::STATUS_DELETED, $automationRule["status"]);
    }

    /**
     *  Test update Status of a recipe
     *
     * @param int $automationRuleID
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws NoResultsException
     * @depends testPost
     */
    public function testUpdateStatusRecipe(int $automationRuleID): void
    {
        // Test permission error
        $this->runWithExpectedException(ForbiddenException::class, function () use ($automationRuleID) {
            $this->runWithUser(function () use ($automationRuleID) {
                $this->api()->put("automation-rules/$automationRuleID/status", ["status" => "active"]);
            }, $this->memberID);
        });

        $this->automationRuleModel->update(
            ["status" => AutomationRuleModel::STATUS_INACTIVE],
            ["automationRuleID" => $automationRuleID]
        );
        $currentRecipe = $this->automationRuleModel->getAutomationRuleByID($automationRuleID);

        $this->assertEquals(AutomationRuleModel::STATUS_INACTIVE, $currentRecipe["status"]);
        $response = $this->api()->put("automation-rules/$automationRuleID/status", ["status" => "active"]);
        $this->assertEquals(200, $response->getStatusCode());
        $automationRule = $response->getBody();
        $this->assertIsArray($automationRule);
        $this->assertEquals($automationRuleID, $automationRule["automationRuleID"]);
        $this->assertEquals("active", $automationRule["status"]);
    }

    /**
     * Test for exceptions while trying to trigger a recipe
     *
     * @return void
     */
    public function testPostTriggerThrowsExceptions(): void
    {
        $this->resetTable("automationRule");
        $this->resetTable("automationRuleRevision");
        // Test permission error
        $this->runWithExpectedException(ForbiddenException::class, function () {
            $this->runWithUser(function () {
                $this->api()->post("automation-rules/1/trigger");
            }, $this->memberID);
        });

        // Test not found
        $this->runWithExpectedExceptionMessage("Automation rule not found.", function () {
            $this->api()->post("automation-rules/999/trigger");
        });
    }
}
