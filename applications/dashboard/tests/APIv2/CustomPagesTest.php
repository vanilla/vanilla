<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Models\CustomPageModel;
use VanillaTests\Dashboard\Utils\CustomPagesApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class CustomPagesTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait, CustomPagesApiTestTrait, ExpectExceptionTrait, EventSpyTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->createUserFixtures();
    }

    /**
     * Test creating a custom page.
     *
     * @return array
     */
    public function testPost(): array
    {
        $customPage = $this->createCustomPage();
        $this->assertIsInt($customPage["customPageID"]);
        $this->assertIsInt($customPage["layoutID"]);

        return $customPage;
    }

    /**
     * Test that a layout is created and check the response for the created layout.
     *
     * @return void
     * @depends testPost
     */
    public function testPostCustomPageLayouts(array $customPage)
    {
        // Check that a layout tied to the page was created.
        $layout = $this->api()
            ->get("/layouts/{$customPage["layoutID"]}", ["expand" => true])
            ->assertSuccess()
            ->getBody();

        $this->assertCount(1, $layout["layoutViews"]);
        $this->assertArrayHasKey("record", $layout["layoutViews"][0]);
        $this->assertSame($customPage["customPageID"], $layout["layoutViews"][0]["record"]["customPageID"]);

        // Test look up and hydrate using the customPage record.
        $customPageLookup = $this->api()
            ->get("/layouts/lookup-hydrate", [
                "layoutViewType" => "customPage",
                "recordType" => "customPage",
                "recordID" => $customPage["customPageID"],
                "params" => [
                    "customPageID" => $customPage["customPageID"],
                ],
            ])
            ->getBody();

        $this->assertArraySubsetRecursive(
            [
                "name" => "test",
                "layoutViewType" => "customPage",
                "layout" => [],
            ],
            $customPageLookup
        );
    }

    /**
     * Test patching a custom page.
     *
     * @param array $customPage
     * @return void
     * @depends testPost
     */
    public function testPatch(array $customPage)
    {
        $this->api()
            ->patch("/custom-pages/{$customPage["customPageID"]}", [
                "seoTitle" => __FUNCTION__,
                "seoDescription" => __FUNCTION__,
                "layoutData" => ["name" => __FUNCTION__],
                "urlcode" => __FUNCTION__,
            ])
            ->assertSuccess()
            ->assertJsonObjectLike([
                "layoutData.name" => __FUNCTION__,
                "seoTitle" => __FUNCTION__,
                "seoDescription" => __FUNCTION__,
                "urlcode" => "/" . __FUNCTION__,
            ]);
    }

    /**
     * Test (soft) deleting a custom page.
     * @return void
     * @depends testPost
     */
    public function testDelete(array $customPage)
    {
        $get = fn() => $this->api()->get("/custom-pages/{$customPage["customPageID"]}");

        // First check that the custom page is still accessible.
        $get()
            ->assertSuccess()
            ->assertJsonObjectLike([
                "customPageID" => $customPage["customPageID"],
                "status" => "published",
            ]);

        $this->api()->delete("/custom-pages/{$customPage["customPageID"]}");

        $this->runWithExpectedException(NotFoundException::class, $get);
    }

    /**
     * Test the `/custom-pages/{customPageID}` endpoint on custom pages of different statuses as a regular member.
     *
     * @return void
     * @throws \Exception
     */
    public function testGetCustomPageWithDifferentConditions()
    {
        $published = $this->createCustomPage(["status" => "published"]);
        $unpublished = $this->createCustomPage(["status" => "unpublished"]);

        // Test getting custom pages with different statuses as a regular member
        $this->runWithUser(function () use ($published, $unpublished) {
            $this->api()
                ->get("/custom-pages/{$published["customPageID"]}")
                ->assertSuccess();

            $this->runWithExpectedException(NotFoundException::class, function () use ($unpublished) {
                $this->api()->get("/custom-pages/{$unpublished["customPageID"]}");
            });
        }, $this->memberID);

        // Test getting non-existant custom page.
        $this->runWithExpectedException(NotFoundException::class, function () {
            $this->api->get("/custom-pages/99999");
        });
    }

    /**
     * Test the `/custom-pages/{customPageID}` endpoint on a custom page with role restrictions.
     *
     * @return void
     * @throws \Exception
     */
    public function testGetCustomPageWithRoleRestrictions()
    {
        $customPageWithRoleRestrictions = $this->createCustomPage(["roleIDs" => [\RoleModel::MOD_ID]]);
        $getCustomPage = fn() => $this->api()->get("/custom-pages/{$customPageWithRoleRestrictions["customPageID"]}");

        // Test get by user with member role.
        $this->runWithExpectedException(NotFoundException::class, function () use ($getCustomPage) {
            $this->runWithUser($getCustomPage, $this->memberID);
        });

        // Test get by user with moderator role. No errors.
        $this->runWithUser($getCustomPage, $this->moderatorID);
    }

    /**
     * Provide data for testing if custom pages with different restrictions are accessible.
     *
     * @return array
     */
    public function provideGetCustomPageWithRestrictionsData(): array
    {
        $modID = \RoleModel::MOD_ID;
        $memberID = \RoleModel::MEMBER_ID;
        return [
            [[], CustomPageModel::CAN_VIEW_NO_RESTRICTIONS, $memberID, false],
            [["roleIDs" => [$modID]], CustomPageModel::CAN_VIEW_NO_RESTRICTIONS, $memberID, true],
            [["roleIDs" => [$modID]], false, $memberID, true],
            [["roleIDs" => [$modID]], CustomPageModel::CAN_VIEW_NO_RESTRICTIONS, $modID, false],
            [["roleIDs" => [$modID]], false, $modID, false],
            [["roleIDs" => [$modID]], true, $modID, false],
            [["roleIDs" => [$modID]], true, $memberID, false],
            [[], false, $memberID, true],
            [[], true, $memberID, false],
        ];
    }

    /**
     * @return void
     * @throws \Exception
     * @dataProvider provideGetCustomPageWithRestrictionsData
     */
    public function testGetCustomPageWithRestrictions(
        array $customPageData,
        int|bool $flags,
        int $roleID,
        bool $expectException
    ): void {
        if ($expectException) {
            $this->expectException(NotFoundException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }
        $user = $this->createUser(["roleID" => $roleID]);
        $customPage = $this->createCustomPage($customPageData);

        $this->runWithBoundEvents(
            fn() => $this->runWithUser(fn() => $this->api()->get("/custom-pages/{$customPage["customPageID"]}"), $user),
            ["customPageModel_canViewCustomPage" => fn() => $flags]
        );
    }

    /**
     * Test creating post types with duplicate URLs.
     *
     * // AIDEV-NOTE: Updated to verify proper "urlcode" field attribution in validation errors
     *
     * @return void
     */
    public function testPostWithDuplicateUrls()
    {
        $this->createCustomPage(["urlcode" => "/duplicate-path"]);

        try {
            $this->createCustomPage(["urlcode" => " duplicate-path// "]);
            $this->fail("Expected ClientException to be thrown for duplicate URL");
        } catch (ClientException $e) {
            $this->assertEquals(422, $e->getCode());
            $this->assertStringContainsString(
                "A custom page with the URL '/duplicate-path' already exists",
                $e->getMessage()
            );

            // Verify the error includes proper field attribution
            $errors = $e->getContext()["errors"] ?? [];
            $this->assertNotEmpty($errors);
            $this->assertEquals("urlcode", $errors[0]["field"]);
            $this->assertStringContainsString("already exists in this site section", $errors[0]["message"]);
        }
    }

    /**
     * Provide data for testing url validation and normalization.
     *
     * @return \Generator
     */
    public function provideUrlTestData(): \Generator
    {
        yield "host and query string stripped" => ["https://vanilla.local/test/path?query=1#test", "/test/path"];
        yield "white space trimmed" => [" /test/path  ", "/test/path"];
        yield "leading slash added, trailing slash removed" => [" test/path/ ", "/test/path"];
        yield "repeated slashes collapsed to one" => ["/test////path////", "/test/path"];

        yield "starts with api" => [
            "/api/test/path",
            null,
            "URL '/api/test/path' cannot start with the reserved path '/api'",
        ];
        yield "starts with sso" => [
            "/sso/test/path",
            null,
            "URL '/sso/test/path' cannot start with the reserved path '/sso'",
        ];
        yield "starts with entry" => [
            "/entry/test/path",
            null,
            "URL '/entry/test/path' cannot start with the reserved path '/entry'",
        ];
        yield "starts with utility" => [
            "/utility/test/path",
            null,
            "URL '/utility/test/path' cannot start with the reserved path '/utility'",
        ];
    }

    /**
     * Test normalization and validation of the url.
     *
     * @return void
     * @dataProvider provideUrlTestData
     */
    public function testPostUrlNormalizationAndValidation(
        string $urlcode,
        ?string $expectedUrlcode,
        ?string $expectException = null
    ) {
        \Gdn::sql()->truncate("customPage");

        if (isset($expectException)) {
            try {
                $this->createCustomPage(["urlcode" => $urlcode]);
                $this->fail("Expected ClientException to be thrown for invalid URL: {$urlcode}");
            } catch (ClientException $e) {
                $this->assertEquals(400, $e->getCode());
                $this->assertStringContainsString($expectException, $e->getMessage());

                // Verify the error includes proper field attribution
                $errors = $e->getContext()["errors"] ?? [];
                $this->assertNotEmpty($errors);
                $this->assertEquals("urlcode", $errors[0]["field"]);
                $this->assertStringContainsString($expectException, $errors[0]["message"]);
            }
        } else {
            $customPage = $this->createCustomPage(["urlcode" => $urlcode]);
            $this->assertSame($expectedUrlcode, $customPage["urlcode"]);
        }
    }

    /**
     * Tests the index endpoint with various filters applied.
     *
     * @return void
     */
    public function testIndex()
    {
        \Gdn::sql()->truncate("customPage");
        $published = $this->createCustomPage(["status" => "published"]);
        $unpublished = $this->createCustomPage(["status" => "unpublished"]);

        $deleted = $this->createCustomPage();
        $this->api()->delete("/custom-pages/{$deleted["customPageID"]}");

        $this->assertApiResults(
            "/custom-pages",
            [],
            [
                "customPageID" => [$published["customPageID"], $unpublished["customPageID"]],
            ],
            count: 2
        );

        $this->assertApiResults(
            "/custom-pages",
            ["status" => "published"],
            ["customPageID" => [$published["customPageID"]]],
            count: 1
        );

        $this->assertApiResults(
            "/custom-pages",
            ["status" => "unpublished"],
            ["customPageID" => [$unpublished["customPageID"]]],
            count: 1
        );
    }

    /**
     * Test creating a custom page with copyLayoutID option.
     *
     * @return void
     * @depends testPost
     */
    public function testPostWithCopyLayoutID(array $sourceCustomPage)
    {
        $newSeoTitle = "Copied Custom Page";
        $newUrlcode = "/copied-page";

        // Create a new custom page using the copyLayoutID option
        $copiedCustomPage = $this->api()
            ->post("/custom-pages", [
                "seoTitle" => $newSeoTitle,
                "seoDescription" => "This is a copied custom page",
                "urlcode" => $newUrlcode,
                "copyLayoutID" => $sourceCustomPage["layoutID"],
            ])
            ->assertSuccess()
            ->getBody();

        // Verify the new custom page was created
        $this->assertIsInt($copiedCustomPage["customPageID"]);
        $this->assertIsInt($copiedCustomPage["layoutID"]);
        $this->assertNotEquals($sourceCustomPage["customPageID"], $copiedCustomPage["customPageID"]);
        $this->assertNotEquals($sourceCustomPage["layoutID"], $copiedCustomPage["layoutID"]);

        // Retrieve the source layout to compare
        $sourceLayout = $this->api()
            ->get("/layouts/{$sourceCustomPage["layoutID"]}")
            ->assertSuccess()
            ->getBody();

        // Retrieve the copied layout to verify it matches the source
        $copiedLayout = $this->api()
            ->get("/layouts/{$copiedCustomPage["layoutID"]}")
            ->assertSuccess()
            ->getBody();

        // Verify that the layout structure was copied
        $this->assertEquals($sourceLayout["layout"], $copiedLayout["layout"]);
        $this->assertEquals($sourceLayout["titleBar"], $copiedLayout["titleBar"]);
        $this->assertEquals($sourceLayout["layoutViewType"], $copiedLayout["layoutViewType"]);

        // Verify that the name was replaced with seoTitle
        $this->assertEquals($newSeoTitle, $copiedLayout["name"]);
        $this->assertNotEquals($sourceLayout["name"], $copiedLayout["name"]);
    }

    /**
     * Test creating a custom page with invalid copyLayoutID.
     *
     * @return void
     */
    public function testPostWithInvalidCopyLayoutID()
    {
        $this->runWithExpectedException(NotFoundException::class, function () {
            $this->api()->post("/custom-pages", [
                "seoTitle" => "Test Page",
                "seoDescription" => "Test description",
                "urlcode" => "/test-invalid-copy",
                "copyLayoutID" => 99999, // Non-existent layout ID
            ]);
        });
    }
}
