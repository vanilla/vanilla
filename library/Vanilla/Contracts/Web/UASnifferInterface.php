<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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
