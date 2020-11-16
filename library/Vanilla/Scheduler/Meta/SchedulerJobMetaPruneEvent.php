<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Meta;

/**
 * Class SchedulerJobMetaPruneEvent
 */
class SchedulerJobMetaPruneEvent {

    /**
     * @var array
     */
    protected $prunedJobMeta;

    /**
     * SchedulerJobMetaPruneEvent constructor.
     *
     * @param array $prunedJobMeta
     */
    public function __construct(array $prunedJobMeta) {
        $this->prunedJobMeta = $prunedJobMeta;
    }

    /**
     * @return array
     */
    public function getPrunedJobMeta(): array {
        return $this->prunedJobMeta;
    }
}
