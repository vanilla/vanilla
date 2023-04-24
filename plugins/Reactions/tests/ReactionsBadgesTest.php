<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

use VanillaTests\SiteTestCase;

/**
 * Test the interactions between badges and reaction.
 */
class ReactionsBadgesTest extends SiteTestCase
{
    public static $addons = ["vanilla"];

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->resetTable("Badge");
        $this->disableAddon("badges");
        $this->disableAddon("reactions");
    }

    /**
     * Test that the reaction badges are added when the badges plugin is already enabled.
     */
    public function testEnableBadges()
    {
        $this->enableAddon("badges");
        $badges = $this->api()
            ->get("/badges", ["type" => "reaction"])
            ->getBody();
        $this->assertEmpty($badges);

        $this->enableAddon("reactions");
        $badges = $this->api()
            ->get("/badges", ["type" => "reaction"])
            ->getBody();
        $this->assertNotEmpty($badges);
    }

    /**
     * Test that the reaction badges are added when the reaction plugin is already enabled.
     */
    public function testEnableReaction()
    {
        $this->enableAddon("reactions");
        $this->enableAddon("badges");
        $badges = $this->api()
            ->get("/badges", ["type" => "reaction"])
            ->getBody();
        $this->assertNotEmpty($badges);
    }

    /**
     * Test that the default badges are not inserted when there is a reaction badge.
     * @psalm-suppress UndefinedClass
     */
    public function testReactionBadgesExists()
    {
        $this->enableAddon("badges");
        $badgeModel = new BadgeModel();

        $badgeModel->define([
            "Name" => __FUNCTION__,
            "Slug" => __FUNCTION__,
            "Type" => "Reaction",
            "Points" => 0,
            "Threshold" => 0,
            "Class" => "test",
            "Level" => 1,
            "CanDelete" => 0,
        ]);

        $this->enableAddon("reactions");
        $badges = $this->api()
            ->get("/badges", ["type" => "reaction"])
            ->getBody();
        $this->assertEquals(1, count($badges));
    }
}
