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
class KibanaElasticHttpClient extends HttpClient
{
    use ScriptLoggerTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct("http://logs.vanilla.localhost");
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
        $this->logger()->title("Kibana Index Patterns");
        $templates = glob(PATH_ROOT . "/docker/logs/elastic/templates/*.json");
        foreach ($templates as $templateFile) {
            $name = str_replace(".json", "", basename($templateFile));
            $url = "/api/saved_objects/index-pattern/{$name}-star";
            $existingResponse = $this->get($url, [], [], ["throw" => false]);
            $hasExisting = $existingResponse->getStatusCode() === 200;

            $this->request(
                $hasExisting ? HttpRequest::METHOD_PUT : HttpRequest::METHOD_POST,
                "/api/saved_objects/index-pattern/{$name}-star",
                [
                    "attributes" => [
                        "title" => "{$name}-*",
                        "timeFieldName" => "@timestamp",
                    ],
                ]
            );
            $this->logger()->info("Created data view pattern <yellow>{$name}-*</yellow>");
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
            ],
        ]);
    }
}
