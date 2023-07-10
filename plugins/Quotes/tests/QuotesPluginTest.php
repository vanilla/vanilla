<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Quotes;

use VanillaTests\SiteTestCase;

/**
 * Tests for the quotes plugin.
 */
class QuotesPluginTest extends SiteTestCase
{
    public static $addons = ["Quotes"];

    /**
     * Test that the quotes preferences are reachable.
     */
    public function testQuotesProfilePage()
    {
        $response = $this->bessy()
            ->getJsonData("/profile/quotes")
            ->asHttpResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
