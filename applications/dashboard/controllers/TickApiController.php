<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Gdn_Statistics as Statistics;
use Vanilla\Analytics\EventProviderService;
use Vanilla\Community\Events\PageViewEvent;

/**
 * API Controller for site analytics.
 */
class TickApiController extends AbstractApiController
{
    /** @var Statistics */
    private $statistics;

    /** @var EventProviderService */
    private $eventProviderService;

    /**
     * TickApiController constructor.
     *
     * @param Statistics $statistics
     * @param EventProviderService $eventProviderService
     */
    public function __construct(Statistics $statistics, EventProviderService $eventProviderService)
    {
        $this->statistics = $statistics;
        $this->eventProviderService = $eventProviderService;
    }

    /**
     * Collect an analytics tick.
     *
     * @return Data
     * @throws ValidationException
     */
    public function post(array $body): Data
    {
        $this->statistics->tick();
        $this->statistics->fireEvent("AnalyticsTick");
        $this->eventProviderService->handleRequest($body);
        return new Data("");
    }
}
