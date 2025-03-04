<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\Api;

use CommentModel;
use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Utils\ArrayUtils;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Events\AiSuggestionAccessEvent;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\FeatureFlagHelper;
use Vanilla\Logging\AuditLogger;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;

/**
 * API Controller for the `/ai-suggestions` resource.
 */
class AiSuggestionsApiController extends \AbstractApiController
{
    const TONE_FRIENDLY = "friendly";

    const TONE_PROFESSIONAL = "professional";

    const TONE_TECHNICAL = "technical";

    const TONES = [self::TONE_FRIENDLY, self::TONE_PROFESSIONAL, self::TONE_TECHNICAL];

    const LEVEL_LAYMAN = "layman";

    const LEVEL_INTERMEDIATE = "intermediate";

    const LEVEL_BALANCED = "balanced";

    const LEVEL_ADVANCED = "advanced";

    const LEVEL_TECHNICAL = "technical";

    const LEVELS = [
        self::LEVEL_LAYMAN,
        self::LEVEL_INTERMEDIATE,
        self::LEVEL_BALANCED,
        self::LEVEL_ADVANCED,
        self::LEVEL_TECHNICAL,
    ];

    const ASSISTANT_USER_MAP = [
        "name" => "Name",
        "icon" => "Photo",
    ];

    protected ConfigurationInterface $config;

    protected \UserModel $userModel;

    protected \UserMetaModel $userMetaModel;

    protected \DiscussionModel $discussionModel;

    /** @var CommentModel  */
    private CommentModel $commentModel;
    protected AiSuggestionSourceService $suggestionSourceService;

    protected bool $auditLogEnabled = true;

    protected \DiscussionsApiController $discussionsApi;

    protected LongRunner $longRunner;

    /**
     * D.I
     *
     * @param ConfigurationInterface $config
     * @param \UserModel $userModel
     * @param CommentModel $commentModel
     * @param AiSuggestionSourceService $suggestionSourceService
     */
    public function __construct(
        ConfigurationInterface $config,
        \UserModel $userModel,
        \UserMetaModel $userMetaModel,
        \DiscussionModel $discussionModel,
        CommentModel $commentModel,
        AiSuggestionSourceService $suggestionSourceService,
        \DiscussionsApiController $discussionsApi,
        LongRunner $longRunner
    ) {
        $this->config = $config;
        $this->userModel = $userModel;
        $this->userMetaModel = $userMetaModel;
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
        $this->suggestionSourceService = $suggestionSourceService;
        $this->discussionsApi = $discussionsApi;
        $this->longRunner = $longRunner;
    }

    /**
     * Retrieves the settings for AI Suggestions.
     *
     * @return Data
     */
    public function get_settings(): Data
    {
        FeatureFlagHelper::ensureFeature("AISuggestions");
        $this->permission("settings.manage");

        $out = $this->schema(
            [
                "enabled:b" => ["default" => false],
                "name:s" => ["default" => ""],
                "icon:s?",
                "toneOfVoice:s?" => ["enum" => self::TONES, "default" => self::TONE_FRIENDLY],
                "levelOfTech:s?" => ["enum" => self::LEVELS, "default" => self::LEVEL_LAYMAN],
                "useBrEnglish:b?" => ["default" => false],
                "sources?" => $this->suggestionSourceService->getSettingsSchema(),
            ],
            "out"
        );

        $settings = $this->config->get("aiSuggestions", []);
        $assistantUserID = $settings["userID"] ?? null;
        $assistantUser = $this->userModel->getID($assistantUserID, DATASET_TYPE_ARRAY);
        if (!empty($assistantUser)) {
            $assistantUser = ArrayUtils::remapProperties($assistantUser, self::ASSISTANT_USER_MAP);
            $meta = $this->userMetaModel->getUserMeta($assistantUserID, "aiAssistant.%", prefix: "aiAssistant.");
            $settings = $meta + $assistantUser + $settings;
        }

        $settings = $out->validate($settings);
        return new Data($settings);
    }

    /**
     * Update the settings for AI Suggestions.
     *
     * @param array $body
     * @return Data
     */
    public function patch_settings(array $body): Data
    {
        FeatureFlagHelper::ensureFeature("AISuggestions");
        $this->permission("settings.manage");

        $in = $this->schema(["enabled:b"]);
        if ($body["enabled"] ?? false) {
            $in->merge(
                Schema::parse([
                    "name:s",
                    "icon:s?",
                    "toneOfVoice:s?" => ["enum" => self::TONES],
                    "levelOfTech:s?" => ["enum" => self::LEVELS],
                    "useBrEnglish:b?" => ["default" => false],
                    "sources?" => $this->suggestionSourceService->getSettingsSchema(),
                ])
            );
        }

        $body = $in->validate($body);

        $signInEvent = new AiSuggestionAccessEvent("configUpdate", $body);
        AuditLogger::log($signInEvent);

        if ($body["enabled"]) {
            $this->createOrUpdateAssistant($body);
        }

        $settings = ArrayUtils::pluck($body, ["enabled", "sources"]);
        foreach ($settings as $name => $setting) {
            if (isset($setting)) {
                $this->config->saveToConfig("aiSuggestions.$name", $setting);
            }
        }

        return $this->get_settings();
    }

    /**
     * Dismiss suggestions by the given IDs for a discussion.
     *
     * @param array $body
     * @return Data
     */
    public function post_dismiss(array $body): Data
    {
        $this->ensureSuggestionsEnabled();
        $in = $this->schema(["discussionID:i", "suggestionIDs:a" => ["items" => ["type" => "integer"]]]);
        $body = $in->validate($body);

        $discussion = $this->discussionsApi->discussionByID($body["discussionID"]);
        $this->checkPermission($discussion);

        $this->suggestionSourceService->toggleSuggestions($discussion, $body["suggestionIDs"]);
        return new Data([], 204);
    }

    /**
     * Restore all suggestions for the given discussion ID.
     *
     * @param array $body
     * @return Data
     */
    public function post_restore(array $body): Data
    {
        $this->ensureSuggestionsEnabled();
        $in = $this->schema(["discussionID:i"]);
        $body = $in->validate($body);

        $discussion = $this->discussionsApi->discussionByID($body["discussionID"]);
        $this->checkPermission($discussion);

        $this->suggestionSourceService->toggleSuggestions($discussion, hide: false);
        return new Data([], 204);
    }

    /**
     * Hide/show suggestions by the given discussion IDs.
     *
     * @param array $body
     * @return Data
     */
    public function post_suggestionsVisibility(array $body): Data
    {
        $this->ensureSuggestionsEnabled();
        $in = $this->schema(["discussionID:i", "visible:b"]);
        $body = $in->validate($body);

        $discussion = $this->discussionsApi->discussionByID($body["discussionID"]);
        $this->checkPermission($discussion);

        $this->suggestionSourceService->updateVisibleSuggestions($discussion, $body["visible"]);
        return new Data([], 204);
    }

    /**
     * Check if a user has the correct permission.
     *
     * @param array $discussion
     * @return void
     * @throws ForbiddenException
     */
    private function checkPermission(array $discussion): void
    {
        if (\Gdn::session()->checkPermission("curation.manage")) {
            return;
        }

        if (\Gdn::session()->UserID === $discussion["InsertUserID"]) {
            return;
        }

        throw new ForbiddenException("You are not allowed to use suggestions.");
    }

    /**
     * Create the AI assistant user and store the userID in the config, or update the user with the stored userID.
     *
     * @param array $data
     * @return void
     */
    private function createOrUpdateAssistant(array $data): void
    {
        $assistantUserID = $this->config->get("aiSuggestions.userID");
        $assistantUserID = $this->userModel->getWhere(["UserID" => $assistantUserID])->value("UserID");
        $userData = ArrayUtils::pluck($data, ["name", "icon"]);
        $userData = ArrayUtils::remapProperties($userData, array_flip(self::ASSISTANT_USER_MAP), true);
        $userData = array_filter($userData, fn($value) => !is_null($value));

        if (empty($assistantUserID)) {
            $assistantUserID = $this->userModel->save(
                $userData + [
                    "Password" => randomString("20"),
                    "HashMethod" => "Random",
                    "Email" => "ai-assistant@stub.vanillacommunity.example",
                    "Photo" => \UserModel::getDefaultAvatarUrl(),
                    "Admin" => "2", // Making it not spoof-able
                ]
            );
        } else {
            $this->userModel->save(["UserID" => $assistantUserID] + $userData);
        }
        $this->validateModel($this->userModel);
        $this->userMetaModel->setUserMeta($assistantUserID, "aiAssistant.toneOfVoice", $data["toneOfVoice"]);
        $this->userMetaModel->setUserMeta($assistantUserID, "aiAssistant.levelOfTech", $data["levelOfTech"]);
        $this->userMetaModel->setUserMeta($assistantUserID, "aiAssistant.useBrEnglish", $data["useBrEnglish"]);
        $this->config->saveToConfig("aiSuggestions.userID", (int) $assistantUserID);
    }

    /**
     * Accept suggestions as accepted answers.
     *
     * @param array $data
     *
     * @return Data
     * @throws ClientException|\Garden\Schema\ValidationException
     */
    public function post_acceptSuggestion(array $data): Data
    {
        $this->ensureSuggestionsEnabled();
        $in = $this->schema([
            "allSuggestions:b",
            "discussionID:i",
            "suggestionIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
                "description" => "List of discussion IDs to delete.",
                "maxItems" => 3,
            ],
        ]);

        $body = $in->validate($data);

        $discussion = $this->discussionsApi->discussionByID($body["discussionID"]);
        $this->checkPermission($discussion);

        $signInEvent = new AiSuggestionAccessEvent("acceptSuggestion", $body);
        AuditLogger::log($signInEvent);
        $schema = $this->commentModel->schema();
        $eventManager = Gdn::getContainer()->get(EventManager::class);
        $eventManager->fire("aiSuggestionsApiController_normalizeComment", $schema);

        $out = $this->schema(
            [
                ":a" => $schema,
            ],
            "out"
        );

        $comments = $this->suggestionSourceService->createComments(
            $body["discussionID"],
            $body["allSuggestions"],
            $body["suggestionIDs"]
        );

        $comments = $out->validate($comments);
        return new Data($comments);
    }

    /**
     * Cancel accepted comment suggestions.
     *
     * @param array $data
     *
     * @return Data
     * @throws ClientException|\Garden\Schema\ValidationException
     */
    public function post_removeAcceptSuggestion(array $data): Data
    {
        $this->ensureSuggestionsEnabled();
        $in = $this->schema([
            "allSuggestions:b",
            "discussionID:i",
            "suggestionIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
                "description" => "List of discussion IDs to delete.",
                "maxItems" => 3,
            ],
        ]);

        $body = $in->validate($data);

        $discussion = $this->discussionsApi->discussionByID($body["discussionID"]);
        $this->checkPermission($discussion);

        $signInEvent = new AiSuggestionAccessEvent("removeSuggestion", $body);
        AuditLogger::log($signInEvent);

        $status = $this->suggestionSourceService->deleteComments(
            $body["discussionID"],
            $body["allSuggestions"],
            $body["suggestionIDs"]
        );
        return new Data(["removed" => $status]);
    }

    /**
     * Handles the /ai-suggestions/generate endpoint.
     *
     * @param array $body
     * @return mixed
     */
    public function put_generate(array $body)
    {
        $this->ensureSuggestionsEnabled();

        $in = $this->schema(["discussionID:i"]);
        $body = $in->validate($body);
        $discussionID = $body["discussionID"];

        $discussion = $this->discussionsApi->discussionByID($discussionID);
        $this->checkPermission($discussion);
        if (strtolower($discussion["Type"]) !== "question") {
            throw new ClientException("Suggestions may only be generated on questions");
        }
        $this->suggestionSourceService->deleteSuggestions($discussionID);
        $action = new LongRunnerAction(AiSuggestionSourceService::class, "generateSuggestions", [$discussionID, true]);
        return $this->longRunner->runApi($action);
    }

    /**
     * This method is intended as a one-liner to check if suggestions are enabled globally and per-user.
     *
     * @return void
     * @throws ClientException
     */
    private function ensureSuggestionsEnabled(): void
    {
        if (!$this->suggestionSourceService->suggestionEnabled()) {
            throw new ClientException("AI Suggestions are not enabled.");
        }
    }
}
