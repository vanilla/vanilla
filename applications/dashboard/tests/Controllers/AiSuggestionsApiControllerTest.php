<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard\Controllers;

use VanillaTests\APIv2\AbstractAPIv2Test;

class AiSuggestionsApiControllerTest extends AbstractAPIv2Test
{
    const VALID_SETTINGS = [
        "enabled" => true,
        "name" => "JarJarBinks",
        "icon" => "https://www.example.com/icon.png",
        "tone" => "professional",
        "level" => "advanced",
        "sources" => [
            "category" => [
                "enabled" => true,
                "exclusionIDs" => [1, 2, 3],
            ],
        ],
    ];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        self::enableFeature("AISuggestions");
    }

    /**
     * Clear settings to test a bare configuration.
     *
     * @return void
     */
    protected function clearSettings(): void
    {
        \Gdn::config()->removeFromConfig("aiSuggestions");
        $assistantUserID = $this->userModel
            ->getWhere(["Email" => "ai-assistant@stub.vanillacommunity.example"])
            ->value("UserID");

        if (!empty($assistantUserID)) {
            $this->userModel->delete($assistantUserID);
        }
    }

    /**
     * Smoke test of the `GET /api/v2/ai-suggestions/settings` endpoint without existing settings.
     *
     * @return void
     */
    public function testGetSettings()
    {
        $this->clearSettings();
        $response = $this->api()->get("/ai-suggestions/settings");
        $this->assertTrue($response->isSuccessful());

        $settings = $response->getBody();
        $this->assertArrayHasKey("enabled", $settings);
        $this->assertFalse($settings["enabled"]);
    }

    /**
     * Test that the `GET /api/v2/ai-suggestions/settings` updates the settings and
     * creates a user with the configured name, icon, tone and level.
     *
     * @return void
     */
    public function testPatchSettings()
    {
        $this->clearSettings();
        $this->api()->patch("/ai-suggestions/settings", self::VALID_SETTINGS);

        $settings = $this->api()
            ->get("/ai-suggestions/settings")
            ->getBody();
        $this->assertEqualsCanonicalizing(self::VALID_SETTINGS, $settings);

        $assistantUserID = \Gdn::config()->get("aiSuggestions.userID");
        $this->assertIsInt($assistantUserID);

        $assistantUser = $this->userModel->getID($assistantUserID, DATASET_TYPE_ARRAY);
        $this->assertSame("JarJarBinks", $assistantUser["Name"]);
        $this->assertSame("https://www.example.com/icon.png", $assistantUser["Photo"]);
        $this->assertSame("professional", $assistantUser["Attributes"]["AiSuggestions"]["Tone"]);
        $this->assertSame("advanced", $assistantUser["Attributes"]["AiSuggestions"]["Level"]);
    }

    /**
     * Smoke test of getting data to render suggestion sources.
     * This just tests that we have a successful response and `category` is one of the built-in sources.
     *
     * @return void
     */
    public function testGetSources()
    {
        $response = $this->api()->get("/ai-suggestions/sources");
        $this->assertTrue($response->isSuccessful());

        $sources = $response->getBody();
        $this->assertArrayHasKey("properties", $sources);
        $this->assertIsArray($sources["properties"]);
        $this->assertArrayHasKey("category", $sources["properties"]);
    }
}
