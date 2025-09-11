<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

interface ReactQueryPreloadProvider
{
    /**
     * Create the preloaded queries.
     *
     * It's recommend to do the fetching here.
     *
     * @return PreloadedQuery[]
     */
    public function createQueries(): array;
}
