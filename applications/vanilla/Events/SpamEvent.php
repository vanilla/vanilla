<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community\Events;

use Vanilla\Forum\Models\SpamReport;

class SpamEvent
{
    public function __construct(public SpamReport $spamReport)
    {
    }
}
