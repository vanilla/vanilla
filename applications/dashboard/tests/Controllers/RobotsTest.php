<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use Gdn;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test the Robots plugin.
 */
class RobotsTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    public static $addons = ["dashboard", "vanilla"];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test robots.txt  through the /robots endpoint.
     */
    public function testPlainRobotsTxt(): void
    {
        $expectedRobotTxt = <<<ROBOTS
User-agent: *
Disallow: /entry/
Disallow: /en/entry/
Disallow: /ssg2-en/entry/
Disallow: /fr/entry/
Disallow: /ssg2-fr/entry/
Disallow: /es/entry/
Disallow: /ssg2-es/entry/
Disallow: /ru/entry/
Disallow: /ssg2-ru/entry/
Disallow: /single-ru/entry/
Disallow: /messages/
Disallow: /en/messages/
Disallow: /ssg2-en/messages/
Disallow: /fr/messages/
Disallow: /ssg2-fr/messages/
Disallow: /es/messages/
Disallow: /ssg2-es/messages/
Disallow: /ru/messages/
Disallow: /ssg2-ru/messages/
Disallow: /single-ru/messages/
Disallow: /profile/comments/
Disallow: /en/profile/comments/
Disallow: /ssg2-en/profile/comments/
Disallow: /fr/profile/comments/
Disallow: /ssg2-fr/profile/comments/
Disallow: /es/profile/comments/
Disallow: /ssg2-es/profile/comments/
Disallow: /ru/profile/comments/
Disallow: /ssg2-ru/profile/comments/
Disallow: /single-ru/profile/comments/
Disallow: /profile/discussions/
Disallow: /en/profile/discussions/
Disallow: /ssg2-en/profile/discussions/
Disallow: /fr/profile/discussions/
Disallow: /ssg2-fr/profile/discussions/
Disallow: /es/profile/discussions/
Disallow: /ssg2-es/profile/discussions/
Disallow: /ru/profile/discussions/
Disallow: /ssg2-ru/profile/discussions/
Disallow: /single-ru/profile/discussions/
Disallow: /search/
Disallow: /en/search/
Disallow: /ssg2-en/search/
Disallow: /fr/search/
Disallow: /ssg2-fr/search/
Disallow: /es/search/
Disallow: /ssg2-es/search/
Disallow: /ru/search/
Disallow: /ssg2-ru/search/
Disallow: /single-ru/search/
Disallow: /sso/
Disallow: /en/sso/
Disallow: /ssg2-en/sso/
Disallow: /fr/sso/
Disallow: /ssg2-fr/sso/
Disallow: /es/sso/
Disallow: /ssg2-es/sso/
Disallow: /ru/sso/
Disallow: /ssg2-ru/sso/
Disallow: /single-ru/sso/
Disallow: /sso
Disallow: /en/sso
Disallow: /ssg2-en/sso
Disallow: /fr/sso
Disallow: /ssg2-fr/sso
Disallow: /es/sso
Disallow: /ssg2-es/sso
Disallow: /ru/sso
Disallow: /ssg2-ru/sso
Disallow: /single-ru/sso
ROBOTS;
        $response = $this->bessy()->get("/robots");
        $this->assertSame($expectedRobotTxt, $response->data("rules")[0]);
    }
}
