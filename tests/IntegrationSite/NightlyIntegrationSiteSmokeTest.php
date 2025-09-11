<?php

namespace VanillaTests\IntegrationSite;

use PHPUnit\Framework\TestCase;

/**
 * Smoke tests for the live integration environment.
 */
class NightlyIntegrationSiteSmokeTest extends TestCase
{
    use IntegrationSiteTrait;

    /**
     * Test getting a response from the /ai-conversations endpoint using nexus.
     *
     * @return void
     */
    public function testRagResponse(): void
    {
        $ragResponse = $this->api()
            ->post("/ai-conversations", ["body" => "Hello world!"])
            ->getBody();
        $this->assertArrayHasKey("messages", $ragResponse);
        $this->assertSame("nexus", $ragResponse["source"]);
    }
}
