<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponseException;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

/**
 * HTTP Client for talking with our local docker kibana instance.
 */
class KibanaHttpClient extends HttpClient
{
    use ScriptLoggerTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct("http://127.0.0.1:5601");
        $this->setDefaultHeader("content-type", "application/json");
        $this->setDefaultHeader("kbn-xsrf", "reporting");
        $this->setThrowExceptions(true);
    }

    /**
     * Perform a healthcheck on the kibana instance.
     *
     * @throws HttpResponseException If the instance is not up.
     */
    public function healthCheck()
    {
        $response = $this->get("/api/status");
    }

    /**
     * Setup index patterns in Kibana.
     */
    public function setupIndexes()
    {
        $this->logger()->title("Kibana Setup");
        $templatesNames = [
            "vanilla-" => "App Logs",
            "nginx-" => "Nginx Logs",
            "vf_" => "Site Search Data",
        ];
        foreach ($templatesNames as $pattern => $title) {
            $attrs = [
                "name" => "{$pattern}*",
                "title" => $title,
                "timeFieldName" => $pattern === "vf_" ? "dateInserted" : "@timestamp",
            ];
            $this->put("/api/saved_objects/index-pattern/{$pattern}star", [
                "upsert" => $attrs,
                "attributes" => $attrs,
            ]);
            $this->logger()->info("Created data view pattern <yellow>{$title}</yellow>");
        }

        // Setup default configs.
        $this->post("/api/kibana/settings", [
            "changes" => [
                "dateFormat:dow" => "Monday",
                "dateFormat:tz" => "US/Eastern",
                "dateFormat" => "DD/MM/YYYY HH:mm:ss",
                "dateNanosFormat" => "DD/MM/YYYY HH:mm:ss.SSSSSSSSS",
                "format:number:defaultLocale" => "en",
                "defaultIndex" => "php-error-star",
                "defaultColumns" => ["message"],
            ],
        ]);

        // Add Dashboards
        $dashboards = file_get_contents(PATH_ROOT . "/docker/logs/elastic/kibana/dashboards.json");
        $dashboards = json_decode($dashboards);
        $this->post("/api/kibana/dashboards/import?force=true", $dashboards);
        $this->logger()->info("<yellow>Created Dashboards</yellow>");
    }
}
