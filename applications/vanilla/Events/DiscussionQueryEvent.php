<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Gdn_SQLDriver;

/**
 * Event for handling discussion query checks.
 */
class DiscussionQueryEvent
{
    /**
     * Constructor.
     *
     * @param Gdn_SQLDriver $innerQuery Add extra where's here.
     * @param Gdn_SQLDriver $outerQuery Add extra join's and selects here.
     */
    public function __construct(public Gdn_SQLDriver &$innerQuery, public Gdn_SQLDriver &$outerQuery)
    {
    }
}
