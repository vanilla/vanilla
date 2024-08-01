<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Redirector;

use Garden\Web\Exception\ResponseException;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Basic tests for the Redirector plugin.
 */
class RedirectorTests extends SiteTestCase
{
    use CommunityApiTestTrait;

    /**
     * @var string[]
     */
    public static $addons = ["Redirector"];

    /**
     * Test that Khoros (Lithium) discussion urls are redirected to the correct destination.
     *
     * @dataProvider provideLithiumDiscussionUrls
     */
    public function testLithiumDiscussionRedirects(string $url): void
    {
        $discussion = $this->createDiscussion();
        $this->assertRedirectContains("discussion/{$discussion["discussionID"]}", $url . $discussion["discussionID"]);
    }

    /**
     * Assert that the target where an url is redirected to contain a specific pattern.
     *
     * @param string $needle
     * @param string $url
     */
    public function assertRedirectContains(string $needle, string $url): void
    {
        try {
            $this->bessy()->get($url);
        } catch (ResponseException $e) {
            $response = $e->getResponse();
        }
        $this->assertNotEmpty($response->getMeta("HTTP_LOCATION"));
        $this->assertStringContainsString($needle, $response->getMeta("HTTP_LOCATION"));
    }

    /**
     * Sample of Khoros (Lithium) discussion urls.
     *
     * @return array[]
     */
    public static function provideLithiumDiscussionUrls(): array
    {
        $r = [["t5/blabla/bla/td-p/"], ["t5/blabla/bla/ba-p/"], ["t5/blabla/bla/ta-p/"], ["t5/blabla/bla/idi-p/"]];

        return $r;
    }
}
