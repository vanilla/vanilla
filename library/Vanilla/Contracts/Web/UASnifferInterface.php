<?php

namespace Vanilla\Contracts\Web;

/**
 * Simple interface for user agent detection.
 *
 * DO NOT USE THIS FOR CACHED CONTENT.
 * WE DO NOT VARY CACHE HEADERS BY USER-AGENT.
 * THIS WILL NOT WORK IN ANY CACHED CONTENT.
 */
interface UASnifferInterface {

    /**
     * DO NOT USE THIS FOR CACHED CONTENT.
     * WE DO NOT VARY CACHE HEADERS BY USER-AGENT.
     * THIS WILL NOT WORK IN ANY CACHED CONTENT.
     *
     * Determine if our browser is IE11 or not.
     */
    public function isIE11(): bool;
}
