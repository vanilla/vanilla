<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Search;

use Garden\Http\HttpResponse;

/**
 * @method dispatchData(mixed $request = null, bool $permanent = true);
 */
trait LegacySearchTestTrait {
    /**
     * Perform a search.
     *
     * @param array $searchParams
     * @return HttpResponse
     */
    protected function performSearch(array $searchParams): HttpResponse {
        $query = http_build_query($searchParams);
        $data = $this->dispatchData("/search?$query")['SearchResults'] ?? null;
        $response = new HttpResponse(
            $data === null ? 500 : 200,
            ['content-type' => 'application/json'],
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        return $response;
    }
}
