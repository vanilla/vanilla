<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use VanillaTests\Dashboard\Utils\CustomPagesApiTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

class CustomPagesTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait, CustomPagesApiTestTrait, ExpectExceptionTrait;

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
     * Test creating post types with duplicate URLs.
     *
     * @return void
     */
    public function testPostWithDuplicateUrls()
    {
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("A custom page with this URL already exists.");
        $this->createCustomPage(["urlcode" => "/duplicate-path"]);
        $this->createCustomPage(["urlcode" => " duplicate-path// "]);
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

        yield "starts with api" => ["api/test/path", null, "URL cannot start with one of the following"];
        yield "starts with sso" => ["sso/test/path", null, "URL cannot start with one of the following"];
        yield "starts with entry" => ["entry/test/path", null, "URL cannot start with one of the following"];
        yield "starts with utility" => ["utility/test/path", null, "URL cannot start with one of the following"];
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
            $this->expectExceptionMessage($expectException);
        }
        $customPage = $this->createCustomPage(["urlcode" => $urlcode]);
        $this->assertSame($expectedUrlcode, $customPage["urlcode"]);
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
     * Tests that a layout associated with a custom page cannot be deleted directly with the layouts api.
     *
     * @return void
     * @depends testPost
     */
    public function testFailureWhenDeletingCustomPageLayout(array $customPage)
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Cannot modify or delete custom page layouts from here.");
        $this->api()->delete("/layouts/{$customPage["layoutID"]}");
    }
}
