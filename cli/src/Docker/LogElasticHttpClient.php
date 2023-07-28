<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;
use Garden\Http\HttpResponseException;
use SebastianBergmann\Invoker\Exception;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\CurrentTimeStamp;

/**
 * HTTP Client for talking with our local elastic log instance.
 */
class LogElasticHttpClient extends HttpClient
{
    use ScriptLoggerTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct("http://127.0.0.1:9200");
        $this->setThrowExceptions(true);
        $this->setDefaultHeader("content-type", "application/json");
    }

    /**
     * Perform a healthcheck on the kibana instance.
     *
     * @throws HttpResponseException If the instance is not up.
     */
    public function healthCheck()
    {
        $this->get("/_cluster/health", [
            "wait_for_status" => "yellow",
            "timeout" => "5s",
        ]);
    }

    /**
     * Extend default error handling by extracting error payloads from the response bodies.
     * @inheritdoc
     */
    public function handleErrorResponse(HttpResponse $response, $options = [])
    {
        $body = $response->getBody();
        $message = "";
        if (is_array($body)) {
            if (isset($body["message"])) {
                $message = $body["message"];
            } elseif (isset($body["error"])) {
                if (is_string($body["error"])) {
                    $message = $body["error"];
                } elseif (is_array($body["error"])) {
                    $message .= $body["error"]["type"] . ": " . $body["error"]["reason"] . "\n";
                    $message = trim($message);
                }
            }
        } else {
            $message = $response->getRawBody();
        }

        throw new HttpResponseException($response, $message ?: $response->getReasonPhrase());
    }

    /**
     * Ensure our indexes and ILM policies are configured.
     */
    public function setupIndexes()
    {
        // Get policies.
        $this->logger()->title("ES - ILM Policies");
        $policyFiles = glob(PATH_ROOT . "/docker/logs/elastic/ilm/*.json");
        foreach ($policyFiles as $policyFile) {
            $name = str_replace(".json", "", basename($policyFile));
            $contents = json_decode(file_get_contents($policyFile));
            $this->put("/_ilm/policy/{$name}", $contents);
            $this->logger()->info("Created policy '{$name}'.");
        }

        // Index templates
        $this->logger()->title("ES - Index Templates");
        $templates = glob(PATH_ROOT . "/docker/logs/elastic/templates/*.json");
        foreach ($templates as $templateFile) {
            $name = str_replace(".json", "", basename($templateFile));
            $date = CurrentTimeStamp::getDateTime()->format("Y.m.d");
            $indexName = $name . "-" . $date;

            try {
                $contents = json_decode(file_get_contents($templateFile));
                $this->put("/_template/{$name}", $contents);
                $this->logger()->info("Created template '{$name}'.");

                // Create an initial timestamped index.
                try {
                    $this->put("/{$indexName}", "");
                } catch (HttpResponseException $ex) {
                    if ($ex->getResponse()->getStatusCode() === 400) {
                        // This is expected if the index already exists.
                    } else {
                        throw $ex;
                    }
                }
                $this->logger()->info("Created initial index '{$indexName}'.");
            } catch (Exception $e) {
                $this->logger()->error("Failed to create index '{$indexName}'");
                throw $e;
            }
        }
    }
}
