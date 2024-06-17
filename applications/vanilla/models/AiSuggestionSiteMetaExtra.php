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
    /**
     * DI.
     *
     * @param AiSuggestionSourceService $aiSuggestionSourceService
     */
    public function __construct(AiSuggestionSourceService $aiSuggestionSourceService)
    {
        $this->aiSuggestionSourceService = $aiSuggestionSourceService;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): array
    {
        $meta = $this->aiSuggestionSourceService->suggestionEnabled();
        return ["aiSuggestion" => $meta];
    }
}
