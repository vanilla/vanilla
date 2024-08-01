<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Spans;

use Vanilla\Utility\Timers;

/**
 * A generic span for tracking anything.
 */
class GenericSpan extends AbstractSpan
{
    public function finish(array $data = []): AbstractSpan
    {
        return parent::finishInternal($data);
    }
}
