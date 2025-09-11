<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Events;

use Garden\Events\ResourceEvent;

class CustomPageEvent extends ResourceEvent
{
    /**
     * @inheritdoc
     */
    public function getApiUrl()
    {
        [$recordType, $recordID] = $this->getRecordTypeAndID();
        return "/api/v2/custom-pages?customPageID={$recordID}";
    }
}
