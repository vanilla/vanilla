<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use DateTime;
use DateTimeInterface;
use Garden\Http\Mocks\MockHttpHandler;
use Garden\Http\Mocks\MockResponse;
use Vanilla\Dashboard\Models\ProductMessageModel;
use Vanilla\Site\OwnSite;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for /api/v2/product-messages
 */
class ProductMessagesTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    const FOREIGN_BASE_URL = "https://fake-success.vanillaforums.com";

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::setConfigs([
            ProductMessageModel::CONF_BASE_URL => self::FOREIGN_BASE_URL,
            ProductMessageModel::CONF_ACCESS_TOKEN => "secret",
        ]);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        MockHttpHandler::clearMock();
        $this->setApplicationVersion(APPLICATION_VERSION);
    }

    /**
     * Test syncing with no config.
     */
    public function testSyncNoConfig(): void
    {
        \Gdn::config()->removeFromConfig("productMessages");
        $this->expectExceptionMessage("Product message base URL or access token not set");
        $this->productMessageModel()->syncAnnouncements();
    }

    /**
     * Test that syncing overrides existing messages and deletes old ones.
     */
    public function testSyncAndOverwrite(): void
    {
        $this->mockForeignAnnouncements([
            $this->mockForeignAnnouncement([
                "discussionID" => 1001,
                "name" => "Post 1",
            ]),
            $this->mockForeignAnnouncement([
                "discussionID" => 1002,
                "name" => "Post 2",
            ]),
            $this->mockForeignAnnouncement([
                "discussionID" => 1003,
                "name" => "Post 3",
            ]),
        ]);

        $this->productMessageModel()->syncAnnouncements();

        $this->api()
            ->get("/product-messages")
            ->assertSuccess()
            ->assertCount(3)
            ->assertJsonArrayValues([
                "name" => ["Post 3", "Post 2", "Post 1"],
            ]);

        $this->mockForeignAnnouncements([
            $this->mockForeignAnnouncement([
                "discussionID" => 1001,
                "name" => "Post 1 updated",
            ]),
            $this->mockForeignAnnouncement([
                "discussionID" => 1002,
                "name" => "Post 2 updated",
            ]),
        ]);

        $this->productMessageModel()->syncAnnouncements();

        $this->api()
            ->get("/product-messages")
            ->assertSuccess()
            ->assertCount(2)
            ->assertJsonArrayValues([
                "name" => ["Post 2 updated", "Post 1 updated"],
            ]);
    }

    /**
     * Test filtering by enabled/disabled features.
     */
    public function testFilterEnabledDisabledFeature(): void
    {
        $this->mockForeignAnnouncements([
            $this->mockForeignAnnouncement(
                [
                    "discussionID" => 1001,
                    "name" => "For custom post pages",
                ],
                enabledFeatures: ["Custom Post Pages"]
            ),
            $this->mockForeignAnnouncement(
                [
                    "discussionID" => 1002,
                    "name" => "For no custom post pages",
                ],
                disabledFeatures: ["Custom Post Pages"]
            ),
        ]);

        self::enableFeature("customLayout.post");
        $this->productMessageModel()->syncAnnouncements();
        $this->api()
            ->get("/product-messages")
            ->assertSuccess()
            ->assertCount(1)
            ->assertJsonArrayValues([
                "name" => ["For custom post pages"],
            ]);

        self::disableFeature("customLayout.post");
        $this->productMessageModel()->syncAnnouncements();
        $this->api()
            ->get("/product-messages")
            ->assertCount(1)
            ->assertJsonArrayValues([
                "name" => ["For no custom post pages"],
            ]);
    }

    /**
     * Testin filtering by release version
     */
    public function testFilterReleaseVersion(): void
    {
        $this->mockForeignAnnouncements([
            $this->mockForeignAnnouncement(
                [
                    "discussionID" => 1001,
                    "name" => "For many",
                ],
                versions: ["2025.001", "2025.002", "2025.003", "2025.LTS2"]
            ),
            $this->mockForeignAnnouncement(
                [
                    "discussionID" => 1002,
                    "name" => "For 2025.005",
                ],
                versions: ["2025.005"]
            ),
            $this->mockForeignAnnouncement(
                [
                    "discussionID" => 1003,
                    "name" => "For custom homepages and lts2",
                ],
                enabledFeatures: ["Custom Home Pages"],
                versions: ["2025.LTS2"]
            ),
        ]);

        $this->setApplicationVersion("2025.LTS2");
        $this->productMessageModel()->syncAnnouncements();
        $this->api()
            ->get("/product-messages")
            ->assertCount(2)
            ->assertJsonArrayValues([
                "name" => ["For custom homepages and lts2", "For many"],
            ]);

        $this->setApplicationVersion("2025.LTS2");
        self::disableFeature("customLayout.home");
        $this->productMessageModel()->syncAnnouncements();
        $this->api()
            ->get("/product-messages")
            ->assertCount(1)
            ->assertJsonArrayValues([
                "name" => ["For many"],
            ]);

        $this->setApplicationVersion("2025.005-SNAPSHOT");
        $this->productMessageModel()->syncAnnouncements();
        $this->api()
            ->get("/product-messages")
            ->assertCount(1)
            ->assertJsonArrayValues([
                "name" => ["For 2025.005"],
            ]);
    }

    /**
     * Test marking messages as read.
     *
     * @return void
     */
    public function testReadStatus(): void
    {
        $this->mockForeignAnnouncements([
            $this->mockForeignAnnouncement([
                "discussionID" => 11000,
                "name" => "Post 1",
            ]),
            $this->mockForeignAnnouncement([
                "discussionID" => 11001,
                "name" => "Post 2",
            ]),
            $this->mockForeignAnnouncement([
                "discussionID" => 11002,
                "name" => "Post 3",
            ]),
        ]);

        $this->productMessageModel()->syncAnnouncements();
        $message1 = $this->api()
            ->get("/product-messages")
            ->assertCount(3)
            ->getBody()[0];

        // Let's mark it as read
        $this->api()
            ->post("/product-messages/{$message1["productMessageID"]}/dismiss")
            ->assertSuccess();

        // Now fetch back the announcements
        $this->api()
            ->get("/product-messages")
            ->assertCount(3)
            ->assertJsonArrayValues([
                "name" => ["Post 3", "Post 2", "Post 1"],
                "isDismissed" => [true, false, false],
                "countViewers" => [1, 0, 0],
            ]);

        // now dismiss them all
        $this->api()
            ->post("/product-messages/dismiss-all")
            ->assertSuccess();

        $this->api()
            ->get("/product-messages")
            ->assertCount(3)
            ->assertJsonArrayValues([
                "name" => ["Post 3", "Post 2", "Post 1"],
                "isDismissed" => [true, true, true],
            ]);
    }

    ///
    /// Utils
    ///

    /**
     * Mock the application version.
     *
     * @param string $version
     * @return void
     */
    private function setApplicationVersion(string $version): void
    {
        self::container()
            ->get(OwnSite::class)
            ->setApplicationVersion($version);
    }

    /**
     * @return ProductMessageModel
     */
    private function productMessageModel(): ProductMessageModel
    {
        return self::container()->get(ProductMessageModel::class);
    }

    /**
     * Create a url on the foreign service.
     *
     * @param string $path
     * @return string
     */
    private function foreignUrl(string $path): string
    {
        return self::FOREIGN_BASE_URL . $path;
    }

    /**
     * Create a mock foreign announcement
     *
     * @param array $overrides
     * @param array $enabledFeatures
     * @param array $disabledFeatures
     * @param array $versions
     * @return array
     */
    private function mockForeignAnnouncement(
        array $overrides,
        array $enabledFeatures = [],
        array $disabledFeatures = [],
        array $versions = []
    ): array {
        $id = self::id();

        $dateInserted = new DateTime("2025-01-01 00:00:00");
        $dateInserted = $dateInserted->modify("+{$id} days");

        return array_replace_recursive(
            [
                "discussionID" => $id,
                "name" => "Hello announcement {$id}",
                "body" => "Body announcement {$id}",
                "dateInserted" => $dateInserted->format(DateTimeInterface::RFC3339_EXTENDED),
                "insertUser" => [
                    "userID" => 48,
                    "name" => "Adam Charron",
                    "url" => $this->foreignUrl("/profile/Adam Charron"),
                    "photoUrl" => "https://somephoto.com/photo.png",
                ],
                "url" => $this->foreignUrl("/discussions/{$id}-some-discussion"),
                "postMeta" => [
                    "enabled-feature" => empty($enabledFeatures) ? ["All"] : $enabledFeatures,
                    "disabled-feature" => empty($disabledFeatures) ? ["None"] : $disabledFeatures,
                    "version" => empty($versions) ? ["All"] : $versions,
                ],
            ],
            $overrides
        );
    }

    /**
     * Mock the API to return the given announcements.
     *
     * @param array $announcements
     * @return void
     */
    private function mockForeignAnnouncements(array $announcements)
    {
        MockHttpHandler::clearMock();
        MockHttpHandler::mock()->mockMulti([
            "GET {$this->foreignUrl("/api/v2/discussions*")}" => MockResponse::json($announcements),
        ]);
    }
}
