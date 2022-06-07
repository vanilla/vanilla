<?php
/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Cloud\Utils;

use Vanilla\Cli\Utils\ShellHttp;
use Vanilla\Cli\Utils\SimpleScriptLogger;

/**
 * Script to log in and view information on Vanilla Console..
 */
class VanillaConsole
{
    /** @var SimpleScriptLogger */
    private $logger;

    /** @var ShellHttp */
    private $http;

    /**
     * Constructor.
     *
     * @param SimpleScriptLogger $logger
     */
    public function __construct(SimpleScriptLogger $logger)
    {
        $this->logger = $logger;
        $this->http = new ShellHttp($logger);
    }

    /**
     * Returns true when the user is logged in.
     * @return bool
     */
    public function checkLoggedIn(): bool
    {
        $response = $this->http->get("https://console.vanilladev.com/");
        return !str_contains($response["url"], "entry/signin");
    }

    /**
     * Log in using username and password.
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function login(string $email, string $password): bool
    {
        $response = $this->http->post("https://console.vanilladev.com/entry/signin", [
            "form" => [
                "Email" => $email,
                "Password" => $password,
            ],
        ]);

        $status = $response["httpCode"];
        if ($status === 200 && !str_contains($response["url"], "entry/signin") && $this->checkLoggedIn()) {
            $this->logger->success("Log in successful.");
            return true;
        } else {
            $this->logger->error("Log in failed.");
        }
        return false;
    }

    /**
     * Get the configuration of a site using it's ID
     *
     * @param int $siteID
     * @return mixed
     */
    public function getConfig(int $siteID)
    {
        $this->logger->info("Fetching config for site $siteID");
        $response = $this->http->get("https://console.vanilladev.com/sites/view.json/config/$siteID");
        $body = $response["body"];

        $this->logger->info("Parsing json");
        $bodyJson = json_decode($body, true);
        $contextPayload = $bodyJson["context"]["payload"];
        if (!$contextPayload) {
            $this->logger->error("Get config failed.");
            exit();
        }
        $decoded = html_entity_decode($contextPayload);
        $config = json_decode($decoded, true);

        return $config;
    }
}
