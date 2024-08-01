<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Models\SiteMetaExtra;

class AiSuggestionSourceMeta extends SiteMetaExtra
{
    protected AiSuggestionSourceService $suggestionSourceService;

    /**
     * Constructor.
     *
     * @param AiSuggestionSourceService $suggestionSourceService
     */
    public function __construct(AiSuggestionSourceService $suggestionSourceService)
    {
        $this->suggestionSourceService = $suggestionSourceService;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): array
    {
        $value = [];
        if (\Gdn::session()->checkPermission("settings.manage")) {
            $value["suggestionSources"] = $this->suggestionSourceService->getSourcesCatalog();
        }
        return $value;
    }
}
