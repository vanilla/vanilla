<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Search;

use Garden\Http\HttpResponse;
use Vanilla\Controllers\SearchRootController;

/**
 * @method dispatchData(mixed $request = null, bool $permanent = true);
 */
trait LegacySearchTestTrait
{
    /**
     * Perform a search.
     *
     * @param array $searchParams
     * @return HttpResponse
     */
    protected function performSearch(array $searchParams): HttpResponse
    {
        \Gdn::themeFeatures()->forceFeatures([
            SearchRootController::ENABLE_FLAG => false,
        ]);
        $data = $this->bessy()->getJsonData("/search", $searchParams)["SearchResults"] ?? null;
        $response = new HttpResponse(
            $data === null ? 500 : 200,
            ["content-type" => "application/json"],
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        return $response;
    }
}
