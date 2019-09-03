<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

use Exception;
use Garden\Http\HttpRequest;
use Vanilla\Web\RequestValidator;

/**
 * A PageScraper class, limited to local files.
 */
class MockPageScraper extends \Vanilla\PageScraper {

    use MockResponseTrait;

    /**
     * Stub out unnecessary param in constructor.
     */
    public function __construct() {
        // Stub in args. We won't need them.
        $validator = \Gdn::getContainer()->get(RequestValidator::class);
        $httpRequest = new HttpRequest();
        parent::__construct($httpRequest, $validator);
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
