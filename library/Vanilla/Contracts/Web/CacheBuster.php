<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Web;

/**
 * An interface for providing cache busting values.
 */
interface CacheBuster {

    /**
     * Get a string capable of busting the cache of an asset when if it changes.
     *
     * @return string
     */
    public function value(): string;
}
