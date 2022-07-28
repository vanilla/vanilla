<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use Garden\Web\Data;
use Vanilla\Contracts\Analytics;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Action provider for analytics defaults.
 */
class AnalyticsActionsProvider implements ReduxActionProviderInterface
{
    /** @var Analytics\ClientInterface */
    private $analyticsClient;

    /**
     * DI.
     *
     * @param Analytics\ClientInterface $analyticsClient
     */
    public function __construct(Analytics\ClientInterface $analyticsClient = null)
    {
        $this->analyticsClient = $analyticsClient;
    }

    /**
     * Create redux actions for analytics config and analytics eventDefaults.
     */
    public function createActions(): array
    {
        $actions = [
            new ReduxAction(ActionConstants::GET_CONFIG, new Data($this->analyticsClient->config()), []),
            new ReduxAction(ActionConstants::GET_EVENT_DEFAULTS, new Data($this->analyticsClient->eventDefaults()), []),
        ];
        return $actions;
    }
}
