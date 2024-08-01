<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\OpenAI;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class to hold details of requests to the OpenAI API.
 */
class OpenAITransaction implements \JsonSerializable
{
    /** @var HttpRequest[]  */
    public array $debugResponses = [];
    public int $countTokens = 0;
    public int $countRequests = 0;

    private OpenAIClient $client;
    private ConfigurationInterface $config;

    /**
     * Constructor.
     */
    public function __construct(OpenAIClient $client, ConfigurationInterface $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function trackResponse(HttpResponse $response)
    {
        if ($this->isDebug()) {
            $this->debugResponses[] = [
                "url" => $response->getRequest()->getUrl(),
                "body" => $response->getRequest()->getBody(),
                "usage" => $response->getBody()["usage"] ?? null,
                "status" => $response->getStatus(),
            ];
        }
        $this->countRequests++;
        $this->countTokens += $response->getBody()["usage"]["total_tokens"] ?? 0;
    }

    private function isDebug()
    {
        return $this->config->get("Debug", $this->config->get("azure.debug"));
    }

    public function finish(): void
    {
        $this->client->clearTransaction();
    }

    public function jsonSerialize(): array
    {
        $result = [
            "countRequests" => $this->countRequests,
            "countTokens" => $this->countTokens,
        ];
        if ($this->isDebug()) {
            $result["responses"] = $this->debugResponses;
        }
        return $result;
    }
}
