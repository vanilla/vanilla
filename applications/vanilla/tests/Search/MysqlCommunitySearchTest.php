<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Search;

use Vanilla\AddonManager;
use Vanilla\Models\AddonModel;
use Vanilla\Search\MysqlSearchDriver;

/**
 * Community search tests for MySQL.
 */
class MysqlCommunitySearchTest extends AbstractCommunitySearchTests
{
    /**
     * @inheritdoc
     */
    protected static function getSearchDriverClass(): string
    {
        return MysqlSearchDriver::class;
    }

    /**
     * Not implemented.
     */
    public function testSearchDiscussionTags()
    {
        $this->markTestSkipped("MySQL driver does not support tag search.");
    }

    /**
     * Test search results' urls having utm parameters.
     */
    public function testUrlHavingUtmParameters()
    {
        $this->createCategory();
        $this->createDiscussion(["name" => "Arcane Signet", "body" => "Arcane Signet"]);
        $this->createDiscussion(["name" => "Wayfarer's Bauble"], ["body" => "Wayfarer's Bauble"]);
        $this->createDiscussion(["name" => "Lantern of the Lost", "body" => "Lantern of the Lost"]);
        $responseBody = $this->api()
            ->get("/search", ["query" => "Bauble"])
            ->getBody();

        $this->assertCount(1, $responseBody);

        $urlComponents = parse_url($responseBody[0]["url"]);

        $this->assertEquals(
            "utm_source=community-search&utm_medium=organic-search&utm_term=Bauble",
            $urlComponents["query"]
        );
    }
}
