<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace ApiV2;

use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test the CivilTongueEx plugin interactions with the API.
 */
class CivilTongueTests extends SiteTestCase
{
    use CommunityApiTestTrait;
    const BAD_WORD = "Scope Creep;";

    public static $addons = ["civiltongueex"];

    /**
     * @inheridoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->container()->get(ConfigurationInterface::class);
        $config->saveToConfig("Plugins.CivilTongue", ["Replacement" => "****", "Words" => self::BAD_WORD]);
    }

    /**
     * Test that the filtering works with the API.
     *
     * @return void
     */
    public function testDiscussionApiFiltering(): void
    {
        $discussion = $this->createDiscussion(["body" => "Scope Creep is a bad thing"]);
        $result = $this->api()
            ->get("discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertEquals("**** is a bad thing", $result["body"]);
    }

    /**
     * Test that filtering is not apply when crawling with the API.
     *
     * @return void
     */
    public function testDiscussionApiCrawlingFiltering(): void
    {
        $this->resetTable("Discussion");
        $this->createDiscussion(["body" => "Scope Creep is a bad thing"]);
        $result = $this->api()
            ->get("discussions", ["expand" => "crawl,tagIDs"])
            ->getBody();
        $this->assertEquals("Scope Creep is a bad thing", $result[0]["body"]);
    }
}
