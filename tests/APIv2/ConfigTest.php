<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Gdn_Configuration;
use LogModel;
use Vanilla\Dashboard\Controllers\API\ConfigApiController;

/**
 * Tests for the /config endpoints.
 */
class ConfigTest extends AbstractAPIv2Test
{
    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->createUserFixtures();
    }

    /**
     * Bump the mod to a community manager.
     */
    protected function startMod(): void
    {
        $this->getSession()->start($this->moderatorID);
        $this->getSession()
            ->getPermissions()
            ->overwrite("Garden.Community.Manage", true);
    }

    /**
     * {@inheritDoc}
     */
    public static function getAddons(): array
    {
        return ["dashboard", "vanilla", "test-config"];
    }

    /**
     * Make sure the config array is filtered by permissions.
     */
    public function testPermissionFiltering(): void
    {
        $this->getSession()->start($this->memberID);
        $r = $this->api()
            ->get("/config")
            ->getBody();

        $this->assertArrayNotHasKey("perms.site.manage", $r);
        $this->assertArrayHasKey("perms.public", $r);

        $this->getSession()->start($this->adminID);
        $r = $this->api()
            ->get("/config")
            ->getBody();
        $this->assertSame("foo", $r["perms.site.manage"]);
        $this->assertSame(true, $r["perms.community.moderate"]);
    }

    /**
     * Make sure the config array is filtered by the query.
     */
    public function testPermissionSelect(): void
    {
        $this->getSession()->start($this->adminID);
        $r = $this->api()
            ->get("/config", ["select" => "perms.site.manage,perms.public"])
            ->getBody();

        $this->assertArrayHasKey("perms.site.manage", $r);
        $this->assertArrayHasKey("perms.public", $r);
        $this->assertCount(2, $r);
    }

    /**
     * Make sure the config array is filtered by the query.
     */
    public function testPermissionSelectWildcard(): void
    {
        $this->getSession()->start($this->adminID);
        $r = $this->api()
            ->get("/config", ["select" => "perms.*"])
            ->getBody();

        $this->assertNotEmpty($r);
        foreach ($r as $key => $_) {
            $this->assertTrue(fnmatch("perms.*", $key), "$key does not match pattern perms.*.");
        }
    }

    /**
     * Test some basic permission updating.
     */
    public function testPermissionPatch(): void
    {
        $this->startMod();
        $r = $this->api()
            ->patch("/config", [
                "perms.community.moderate" => false,
            ])
            ->getBody();

        $this->getSession()->start($this->adminID);
        $r = $this->api()->patch("/config", [
            "perms.site.manage" => "bar",
            "perms.public" => 234,
        ]);

        $r = $this->api()
            ->get("/config")
            ->getBody();
        $this->assertSame(234, $r["perms.public"]);
        $this->assertSame("bar", $r["perms.site.manage"]);
        $this->assertSame(false, $r["perms.community.moderate"]);
    }

    /**
     * Test some basic permission patch permission errors..
     */
    public function testPermissionPatchPermissions(): void
    {
        $this->startMod();

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $r = $this->api()
            ->patch("/config", [
                "perms.site.manage" => "bar",
                "perms.public" => 234,
            ])
            ->getBody();
    }

    /**
     * Validate the permissions schema.
     */
    public function testPermissionsSchema(): void
    {
        $schema = $this->api()
            ->get("/config/schema")
            ->getBody();

        foreach ($schema["properties"] as $key => $item) {
            // Validate the permission names
            if (isset($item["x-read"])) {
                $this->assertContains($item["x-read"], ConfigApiController::READ_PERMS);
            }
            if (isset($item["x-write"])) {
                $this->assertContains($item["x-write"], ConfigApiController::WRITE_PERMS);
            }
        }
    }

    /**
     * Test that properties are mapped correctly when writing.
     */
    public function testPatchCorrectName()
    {
        $this->assertConfigValue("Feature.useNewSearchPage.Enabled", null);

        $this->api->patch("/config", [
            "labs.newSearchPage" => true,
        ]);

        $this->assertConfigValue("Feature.useNewSearchPage.Enabled", true);
        $this->assertConfigValue("labs.newSearchPage", null);
    }

    /**
     * Test that properties are mapped correctly when writing, and test that the change is recorded in the log table.
     */
    public function testPatchHTMLInjection()
    {
        $r = $this->api()
            ->get("/config")
            ->getBody();
        $expected = [
            "Garden.Description" => stringIsNullOrEmpty($r["garden.description"]) ? false : $r["garden.description"],
            "Garden.Title" => $r["garden.title"],
            "_New" => ["Garden.Description" => "Something TO Show", "Garden.Title" => "Hello moo"],
        ];

        $this->api->patch("/config", [
            "garden.description" => "<title>Something TO Show</title>",
            "garden.title" => "Hello <all>moo<all>",
            "seo.metaHtml" => "<meta name='allowsHtml' content='yes' />", // This one is annotated to allow HTML.
        ]);

        $this->assertConfigValue("Garden.Description", "Something TO Show");
        $this->assertConfigValue("Garden.Title", "Hello moo");
        $this->assertConfigValue("seo.metaHtml", "<meta name='allowsHtml' content='yes' />");

        // Test Log Model has saved configuration change.
        $config = $this->container()->get(Gdn_Configuration::class);
        $config->shutdown();
        $logModel = $this->container()->get(LogModel::class);
        //Load last config edit entry.
        $record = $logModel->getWhere(
            ["recordType" => "Configuration", "operation" => "Edit"],
            "RecordDate",
            "desc",
            0,
            1
        )[0];
        $data = $record["Data"];

        $this->assertSame($expected["Garden.Title"], $data["Garden.Title"]);
        $this->assertSame($expected["Garden.Description"], $data["Garden.Description"]);
        $this->assertSame($expected["_New"]["Garden.Title"], $data["_New"]["Garden.Title"]);
        $this->assertSame($expected["_New"]["Garden.Description"], $data["_New"]["Garden.Description"]);
    }

    /**
     * Test that the x-upload attribute transforms upload fragments into upload urls.
     */
    public function testUploadUrls()
    {
        $this->api->patch("/config", [
            "branding.bannerImage" => "/test123.png",
        ]);
        $response = $this->api->get("/config", ["select" => "branding.bannerImage"])->getBody();
        $this->assertEquals(["branding.bannerImage" => url("/uploads/test123.png", true)], $response);
    }
}
