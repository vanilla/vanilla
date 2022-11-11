<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * LongRunnerAction composing multiple other actions together.
 */
class LongRunnerMultiAction extends LongRunnerAction
{
    /**
     * DI.
     *
     * @param LongRunnerAction[] $actions
     */
    public function __construct(array $actions, array $options = [])
    {
        parent::__construct(LongRunner::class, "composeMultipleActions", [$actions], $options);
    }
}
