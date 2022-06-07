<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Http\HttpRequest;
use Gdn;
use Vanilla\Web\SafeCurlHttpHandler;
use VanillaTests\BootstrapTestCase;

/**
 * Class SafeCurlHttpHandlerTest
 * @package VanillaTests\Library\Vanilla\Web
 */
class SafeCurlHttpHandlerTest extends BootstrapTestCase
{
    /**
     * Provide test data for testNoExceptionThrown.
     *
     * @return string[][]
     */
    public function provideTestNoExceptionThrownData(): array
    {
        $r = [
            "notUrl" => ["not-a-url"],
            "badIP" => ["http://0.0.0.0:123"],
            "someRandomUrl" => ["https://www.someurl.com"],
        ];

        return $r;
    }

    /**
     * Test that our SafeCurlHttpHandler catches all exceptions.
     *
     * @param string $url Url to direct the request to.
     * @throws \Garden\Container\ContainerException Container exception.
     * @throws \Garden\Container\NotFoundException Not Found exception.
     * @dataProvider provideTestNoExceptionThrownData
     */
    public function testNoExceptionThrown(string $url)
    {
        $request = Gdn::getContainer()->get(HttpRequest::class);
        $request->setMethod("POST");
        $request->setUrl($url);
        $request->setBody("don't throw an error.");
        $safeCurl = Gdn::getContainer()->get(SafeCurlHttpHandler::class);
        try {
            $response = $safeCurl->send($request);
            $rawBody = json_decode($response->getRawBody(), true);
            $this->assertArrayHasKey("error", $rawBody);
        } catch (\Exception $e) {
            $this->fail("SafeCurl should catch all exceptions");
        }
    }
}
