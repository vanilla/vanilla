<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

use Exception;
use Garden\Http\HttpRequest;

/**
 * A PageScraper class, limited to local files.
 */
class MockPageScraper extends \Vanilla\PageScraper {

    use MockResponseTrait;

    /**
     * Stub out unnecessary param in constructor.
     */
    public function __construct() {
        // Stub in an empty request. We won't need it.
        $httpRequest = new HttpRequest();
        parent::__construct($httpRequest);
    }

    /**
     * Override to pull from mock responses.
     * @inheritdoc
     */
    public function pageInfo(string $url): array {
        $key = $this->makeMockResponseKey($url);
        if ($this->mockedResponses[$key]) {
            return $this->mockedResponses[$key];
        } else {
            throw new Exception("No mock result found for url $url.");
        }
    }
}
