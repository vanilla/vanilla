<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Models\SiteMetaExtra;

class SuggestedContentMeta extends SiteMetaExtra
{
    /**
     * D.I.
     *
     * @param InterestModel $interestModel
     */
    public function __construct(private InterestModel $interestModel)
    {
    }

    /**
     * @inheritDoc
     */
    public function getValue(): array
    {
        return [
            "suggestedContentEnabled" => $this->interestModel->isSuggestedContentEnabled(),
        ];
    }
}
