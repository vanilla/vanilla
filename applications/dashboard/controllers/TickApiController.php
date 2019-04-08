<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;
use Gdn_Statistics as Statistics;
use Garden\Schema\Schema;

/**
 * API Controller for site analytics.
 */
class TickApiController extends AbstractApiController {

    /** @var Statistics */
    private $statistics;

    /**
     * TickApiController constructor.
     * @param Statistics $statistics
     */
    public function __construct(Statistics $statistics) {
        $this->statistics = $statistics;
    }

    /**
     * Collect an analytics tick.
     *
     * @return Data
     */
    public function post(): Data {
        $this->statistics->tick();
        $this->statistics->fireEvent("AnalyticsTick");
        return new Data('');
    }
}
