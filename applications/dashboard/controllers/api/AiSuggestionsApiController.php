<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Utils\ArrayUtils;
use Garden\Web\Data;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;
use Vanilla\FeatureFlagHelper;

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
        "tone" => "Attributes.AiSuggestions.Tone",
        "level" => "Attributes.AiSuggestions.Level",
    ];

    protected ConfigurationInterface $config;

    protected \UserModel $userModel;

    protected AiSuggestionSourceService $suggestionSourceService;

    /**
     * D.I
     *
     * @param ConfigurationInterface $config
     * @param \UserModel $userModel
     * @param AiSuggestionSourceService $suggestionSourceService
     */
    public function __construct(
        ConfigurationInterface $config,
        \UserModel $userModel,
        AiSuggestionSourceService $suggestionSourceService
    ) {
        $this->config = $config;
        $this->userModel = $userModel;
        $this->suggestionSourceService = $suggestionSourceService;
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
                "icon:s" => ["default" => ""],
                "tone:s" => ["enum" => self::TONES, "default" => self::TONE_FRIENDLY],
                "level:s" => ["enum" => self::LEVELS, "default" => self::LEVEL_LAYMAN],
                "sources?" => $this->suggestionSourceService->getSourcesSchema(),
            ],
            "out"
        );

        $settings = $this->config->get("aiSuggestions", []);
        if (!empty($settings["userID"])) {
            $assistantUser = $this->userModel->getID($settings["userID"], DATASET_TYPE_ARRAY);
            $assistantUser = ArrayUtils::remapProperties($assistantUser, self::ASSISTANT_USER_MAP);
            $settings = $assistantUser + $settings;
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

        $in = $this->schema([
            "enabled:b",
            "name:s",
            "icon:s",
            "tone:s" => ["enum" => self::TONES],
            "level:s" => ["enum" => self::LEVELS],
            "sources" => $this->suggestionSourceService->getSettingsSchema(),
        ]);

        $body = $in->validate($body);

        $assistantUser = ArrayUtils::pluck($body, ["name", "icon", "tone", "level"]);
        $assistantUser = ArrayUtils::remapProperties($assistantUser, array_flip(self::ASSISTANT_USER_MAP), true);
        $this->createOrUpdateAssistant($assistantUser);

        $settings = ArrayUtils::pluck($body, ["enabled", "sources"]);
        foreach ($settings as $name => $setting) {
            $this->config->saveToConfig("aiSuggestions.$name", $setting);
        }

        return $this->get_settings();
    }

    /**
     * Get the schema for updating suggestion sources
     *
     * @return Data
     */
    public function get_sources(): Data
    {
        FeatureFlagHelper::ensureFeature("AISuggestions");
        $this->permission("settings.manage");

        $schema = $this->suggestionSourceService->getSourcesSchema();

        return new Data($schema);
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

        if (empty($assistantUserID)) {
            $assistantUserID = $this->userModel->save(
                [
                    "Password" => randomString("20"),
                    "HashMethod" => "Random",
                    "Email" => "ai-assistant@stub.vanillacommunity.example",
                ] + $data
            );
            $this->config->saveToConfig("aiSuggestions.userID", (int) $assistantUserID);
        } else {
            $this->userModel->save(["UserID" => $assistantUserID] + $data);
        }
        $this->validateModel($this->userModel);
    }
}
