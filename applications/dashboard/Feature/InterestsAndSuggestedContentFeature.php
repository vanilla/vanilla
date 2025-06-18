<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Feature;

use Vanilla\Dashboard\Models\InterestModel;
use Vanilla\Feature\Feature;
use Vanilla\FeatureFlagHelper;

class InterestsAndSuggestedContentFeature extends Feature
{
    /**
     * Constructor.
     */
    public function __construct(private InterestModel $interestModel)
    {
        parent::__construct("Interests & Suggested Content");
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled(InterestModel::SUGGESTED_CONTENT_FEATURE_FLAG) &&
            $this->interestModel->selectPagingCount([], limit: 1) > 0;
    }
}
