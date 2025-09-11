<?php
/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Models\SiteMetaExtra;

class ModerationMeta extends SiteMetaExtra
{
    /**
     * @inheritdoc
     */
    public function getValue(): array
    {
        return [
            "moderation" => [
                "restrictMemberFilterUI" => \Gdn::config("moderation.restrictMemberFilterUI", false),
            ],
        ];
    }
}
