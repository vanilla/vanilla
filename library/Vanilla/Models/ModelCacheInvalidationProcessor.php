<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Database\Operation;

/**
 * Processor for doing some invalidation of cache.
 */
class ModelCacheInvalidationProcessor extends Operation\InvalidateCallbackProcessor {

    /** @var ModelCache */
    private $cache;

    /**
     * @param ModelCache $cache
     */
    public function __construct(ModelCache $cache) {
        $this->cache = $cache;
        parent::__construct(function () {
            $this->cache->invalidateAll();
        });
    }
}
