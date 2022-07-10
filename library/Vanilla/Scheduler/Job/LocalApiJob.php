<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Garden\Http\HttpClient;
use Vanilla\InjectableInterface;
use Vanilla\Http\InternalClient;

/**
 * Local job for with access to an internal http client.
 */
abstract class LocalApiJob implements LocalJobInterface, InjectableInterface {

    /** @var InternalClient */
    protected $vanillaClient;

    /**
     * Local job for updating individual requests in elasticsearch.
     *
     * @param InternalClient $internalClient
     */
    public function setDependencies(InternalClient $internalClient) {

        // Make an internal http client.
        $internalClient->setBaseUrl('');
        $internalClient->setThrowExceptions(true);
        $this->vanillaClient = $internalClient;
    }
}
