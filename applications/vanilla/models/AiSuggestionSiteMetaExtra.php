<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Dashboard\Models\AiSuggestionSourceService;

/**
 * Class for adding extra site meta related to AI suggestion.
 */
class AiSuggestionSiteMetaExtra extends \Vanilla\Models\SiteMetaExtra
{
    private AiSuggestionSourceService $aiSuggestionSourceService;
    protected \UserModel $userModel;

    /**
     * DI.
     *
     * @param AiSuggestionSourceService $aiSuggestionSourceService
     */
    public function __construct(AiSuggestionSourceService $aiSuggestionSourceService, \UserModel $userModel)
    {
        $this->aiSuggestionSourceService = $aiSuggestionSourceService;
        $this->userModel = $userModel;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): array
    {
        $answerSuggestionsEnabled = $this->aiSuggestionSourceService->suggestionFeatureEnabled();
        $assistantID = $this->aiSuggestionSourceService->aiSuggestionConfigs()["userID"] ?? 0;
        $aiAssistant = null;
        if ($assistantID > 0) {
            $aiAssistant = $this->userModel->getFragmentByID($assistantID, true);
        }
        return [
            "answerSuggestionsEnabled" => $answerSuggestionsEnabled,
            "aiAssistant" => $aiAssistant,
        ];
    }
}
