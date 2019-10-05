<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Adapters;

use Psr\SimpleCache\CacheInterface;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Basic integration smoke tests for adapters.
 */
class AdaptersTest extends SharedBootstrapTestCase {

    /**
     * Smoke test the simple cache adapter.
     */
    public function testSimpleCacheContainer() {
        $cache = static::container()->get(CacheInterface::class);

        $this->assertInstanceOf(CacheInterface::class, $cache);
    }
}
