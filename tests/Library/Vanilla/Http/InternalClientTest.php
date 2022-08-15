<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Http;

use VanillaTests\SiteTestCase;

/**
 * Tests for the internal client.
 *
 * These are relatively minimal as internal request is already heavily used throughout our test suite.
 */
class InternalClientTest extends SiteTestCase
{
    /**
     * Test that our internal request does not pollute our headers.
     */
    public function testInternalClientNoRequestPollution()
    {
        $headers = headers_list();
        $this->api->get("/users/\$me");
        $this->assertSame(headers_list(), $headers);
    }
}
