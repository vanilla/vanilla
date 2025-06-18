<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace tests;

use Gdn_Configuration;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the /config endpoints.
 */
class StubbedContentTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;

    public static $addons = ["stubcontent"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->createUserFixtures();
    }

    /**
     * Test changing locale on the site, saved and has desired outcome.
     */
    public function testLanguageChange(): void
    {
        // Get Baseline
        $this->getSession()->start($this->adminID);
        $r = $this->api()
            ->get("/config", ["select" => "garden.locale"])
            ->getBody();

        $this->assertArrayHasKey("garden.locale", $r);
        $this->assertEquals("en", $r["garden.locale"]);

        $r = $this->api()->patch("/config", [
            "garden.locale" => "fr",
        ]);

        $this->assertEquals(200, $r->getStatusCode());
        $config = $this->container()->get(Gdn_Configuration::class);
        $config->shutdown();
        // Making sure change gets stuck.
        $r = $this->api()
            ->get("/config", ["select" => "garden.locale"])
            ->getBody();

        $this->assertArrayHasKey("garden.locale", $r);
        $this->assertEquals("fr", $r["garden.locale"]);

        //Stub Content updated locale setting per user of Attributes.StubLocale

        $user = $this->userModel->getID(3, DATASET_TYPE_ARRAY);
        $this->assertArrayHasKey("Attributes", $user);
        $this->assertEquals("fr", $user["Attributes"]["StubLocale"]);
    }
}
