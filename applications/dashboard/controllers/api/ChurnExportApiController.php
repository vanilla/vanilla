<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Exception;
use Garden\Http\HttpClient;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\Events\ExportAccessEvent;
use Vanilla\Logging\AuditLogger;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Site\OwnSite;
use Vanilla\Utility\DebugUtils;
use Vanilla\Web\Controller;

/**
 * API controller for the churn export.
 */
class ChurnExportApiController extends Controller
{
    private HttpClient $managementDashboard;
    private OwnSite $ownSite;

    /**
     * @param OwnSite $ownSite
     * @param LoggerInterface $logger
     * @throws ClientException
     */
    public function __construct(OwnSite $ownSite, LoggerInterface $logger)
    {
        $isTestMode = DebugUtils::isTestMode();
        if (!class_exists(\Communication::class) && !$isTestMode) {
            throw new ClientException("You need to enable the vfshared plugin to use this feature.");
        }
        $this->ownSite = $ownSite;
        $this->managementDashboard = !$isTestMode
            ? \Communication::managementDashboard()
            : new HttpClient("https://management-dashboard.vanilla.localhost");
    }

    /**
     * Generate a URL for the churn data export.
     *
     * @return Data
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\PermissionException
     */
    public function get_exportUrl(): Data
    {
        $this->permission(["site.manage", "exports.manage"]);
        if (\Gdn::session()->isSpoofedInUser()) {
            throw new ClientException("You are not allowed to access this feature.");
        }
        try {
            $siteID = $this->ownSite->getSiteID();
            $user = \Gdn::session()->User;
            if (!$this->refreshExportUrl($siteID)) {
                throw new ServerException("Error refreshing the export URL.");
            }
            $response = $this->managementDashboard->get(
                "/api/site/{$siteID}/export",
                [],
                ["content-type" => "application/json"]
            );

            if (!$response->isSuccessful()) {
                throw $response->asException();
            }
            $body = $response->getBody();
            if (!isset($body["status"]) || $body["status"] !== "completed") {
                throw new ServerException("Export is not yet available for download.");
            }
            $url = $body["data"]["s3"]["download"]["signed_url"];
            //Mark an Entry in the audit log
            AuditLogger::log(new ExportAccessEvent($siteID, $body["data"]["backup_id"], $url));
        } catch (Exception $e) {
            ErrorLogger::error("Error generating export URL", ["exports"], ["exception" => $e]);
            throw $e;
        }
        return new Data([
            "status" => "success",
            "exportUrl" => $url,
        ]);
    }

    /**
     * Check if an url is available for download
     */
    public function get_exportAvailable(): Data
    {
        $this->permission(["site.manage", "exports.manage"]);
        $data = [
            "exportAvailable" => false,
        ];

        $siteID = $this->ownSite->getSiteID();
        $response = $this->managementDashboard->get(
            "/api/site/{$siteID}/export",
            [],
            ["content-type" => "application/json"]
        );

        if ($response->isSuccessful()) {
            $responseData = $response->getBody();
            if ($responseData["available"]) {
                $data["exportAvailable"] = true;
                $data["exportStatus"] = $response["status"];
                if (isset($response["data"]["s3"])) {
                    $data["exportExpiry"] = \DateTimeImmutable::createFromFormat(
                        "Y-m-d-H-i-s",
                        $response["data"]["s3"]["object"]["expires_at"]
                    );
                }
            }
        } else {
            ErrorLogger::error("Error checking export availability", ["exports"], ["response" => $response]);
        }
        $out = $this->schema(Schema::parse(["exportAvailable:b", "exportStatus:s?", "exportExpiry:dt?"]));
        $out->validate($data);
        return new Data($data);
    }

    /**
     * Refresh the export URL
     *
     * @return bool
     */
    private function refreshExportUrl(int $siteID): bool
    {
        $response = $this->managementDashboard->post("/api/site/{$siteID}/export/refresh");
        if ($response->isSuccessful()) {
            return true;
        }
        ErrorLogger::error("Error refreshing URL", ["exports"], ["response" => $response]);
        return false;
    }
}
