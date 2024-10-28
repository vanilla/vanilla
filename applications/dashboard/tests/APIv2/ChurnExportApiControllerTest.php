<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\APIv2;

use Garden\Http\Mocks\MockHttpHandler;
use Garden\Http\Mocks\MockResponse;
use Vanilla\CurrentTimeStamp;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

class ChurnExportApiControllerTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setConfig("auditLog.enabled", true);
    }

    /**
     * Test checking export available when export is not available.
     *
     * @return void
     */
    public function testExportNotAvailable(): void
    {
        MockHttpHandler::mock()->mockMulti([
            "GET /api/site/*/export" => MockResponse::json(["error" => "Site Data Not Found.", "available" => false]),
        ]);
        $response = $this->api()->get("/churn-export/export-available");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(false, $response["exportAvailable"]);
    }

    /**
     * Test checking export available when export is available.
     *
     * @return void
     */
    public function testExportAvailable(): void
    {
        MockHttpHandler::mock()->mockMulti([
            "GET /api/site/*/export" => MockResponse::json(["available" => true, "status" => "completed"]),
        ]);
        $response = $this->api()->get("/churn-export/export-available");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(true, $response["exportAvailable"]);
    }

    /**
     * Test that export available logs error when management dashboard server is not responsive.
     *
     * @return void
     */
    public function testExportAvailableWhenSiteIsNotResponsive()
    {
        MockHttpHandler::mock()->mockMulti([
            "GET /api/site/*/export" => MockResponse::notFound(),
        ]);
        $response = $this->api()->get("/churn-export/export-available");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(false, $response["exportAvailable"]);
        $this->assertErrorLogMessage("Error checking export availability");
    }

    /**
     * Test that when export is available it returns the export URL.
     */
    public function testGetExportUrl(): void
    {
        $currentUser = $this->getSession()->User;
        $currentDate = CurrentTimeStamp::getDateTime();
        $currentDateFormatted = $currentDate->format("Y-m-d-H-i-s");
        $expiryDate = $currentDate->modify("+1 week")->format("Y-m-d-H-i-s");
        $urlExpiry = $currentDate->modify("+12 hours")->format("Y-m-d-H-i-s");
        $url =
            "https://hlv-customer-export.s3.amazonaws.com/123/Customer_Site-123-full_export-$currentDateFormatted-.zip?X-Amz-Signature=" .
            randomString(20, "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789");
        MockHttpHandler::mock()->mockMulti([
            "GET /api/site/*/export" => MockResponse::json([
                "status" => "completed",
                "available" => true,
                "data" => [
                    "s3" => [
                        "object" => [
                            "expires_at" => $expiryDate,
                            "key" => "123/Customer_Site-123-full_export-$currentDateFormatted.zip",
                        ],
                        "download" => [
                            "expires_at" => $urlExpiry,
                            "signed_url" => $url,
                        ],
                    ],
                    "site_id" => 123,
                    "backup_id" => 777,
                    "site_domain" => "example.vanillaTest.com",
                    "customer_name" => "Customer_Site",
                    "started_export_at" => $currentDate->format("Y-m-d-H-i-s"),
                    "finished_export_at" => $currentDate->modify("+90 minutes")->format("Y-m-d-H-i-s"),
                ],
            ]),
            "POST /api/site/*/export/refresh" => MockResponse::success(),
        ]);
        $response = $this->api()->get("/churn-export/export-url");
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($response["status"], "success");
        $this->assertEquals($response["exportUrl"], $url);
        $this->assertLogMessage("User generated a URL link for data export ending in - `" . substr($url, -5)) . "`";
    }

    /**
     * Test that export url endpoint throws errors on people who doesn't have permission.
     *
     * @return void
     */
    public function testPermissionError(): void
    {
        $user = $this->createUser(["email" => "testuser@example.com"]);
        $this->runWithUser(function () {
            $this->expectExceptionCode(403);
            $this->expectExceptionMessage("Permission Problem");
            $this->api()->get("/churn-export/export-url");
        }, $user);
    }

    /**
     * Test that an exception is thrown if the url cannot be refreshed
     */
    public function testRefreshExportUrlError(): void
    {
        MockHttpHandler::mock()->mockMulti([
            "POST /api/site/*/export/refresh" => MockResponse::notFound(),
        ]);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("Error refreshing the export URL.");
        $this->api()->get("/churn-export/export-url");
    }

    private function canRunTest()
    {
        return class_exists(\Communication::class);
    }
}
