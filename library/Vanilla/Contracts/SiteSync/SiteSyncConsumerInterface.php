<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\SiteSync;

use Garden\Http\HttpClient;

/**
 * Defines interface that consumes data when synchronizing from site sync source to site sync destination.
 */
interface SiteSyncConsumerInterface {

    /**
     * Perform any necessary setup on the sync destination prior to consuming data from a sync source
     */
    public function setup(): void;

    /**
     * Consume content to synchronize to destination via authenticated API v2 HTTP client.
     * Assumes content to consume represents the entire data set.
     *
     * @param HttpClient $destinationClient Authenticated API v2 HTTP client for the destination site
     * @param array $content Content to be consumed as part of site synchronization
     */
    public function consumeAllApi(HttpClient $destinationClient, array $content) : void;
}
