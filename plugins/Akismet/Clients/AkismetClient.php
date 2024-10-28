<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Akismet\Clients;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\Contracts\ConfigurationInterface;

class AkismetClient extends HttpClient
{
    private const API_BASE_URL = "rest.akismet.com";

    /**
     * D.I.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config)
    {
        $server = $config->get("Plugins.Akismet.Server", self::API_BASE_URL);
        $apiKey = $config->get("Plugins.Akismet.Key", $config->get("Plugins.Akismet.MasterKey"));
        parent::__construct("https://$server/1.1");
        $this->setDefaultHeader("Content-Type", "application/x-www-form-urlencoded");
        $this->addMiddleware(function (HttpRequest $request, callable $next) use ($apiKey) {
            $body = $request->getBody();
            $body .= empty($body) ? "" : "&";
            $body .= http_build_query(["api_key" => $apiKey, "blog" => \Gdn::request()->url("/", true)]);
            $request->setBody($body);
            return $next($request);
        });
    }

    /**
     * Verifies the key.
     *
     * @param array $data
     * @return HttpResponse
     */
    public function verifyKey(array $data): HttpResponse
    {
        $body = http_build_query($data);
        return $this->post("/verify-key", $body);
    }

    /**
     * Check a forum post for spam.
     *
     * @param array $data
     * @return HttpResponse
     */
    public function commentCheck(array $data): HttpResponse
    {
        $body = http_build_query($data);
        return $this->post("/comment-check", $body);
    }

    /**
     * Sends missed spam data to Akismet for analysis.
     *
     * @param array $data
     * @return HttpResponse
     */
    public function submitSpam(array $data): HttpResponse
    {
        $body = http_build_query($data);
        return $this->post("/submit-spam", $body);
    }

    /**
     * Sends false positive data to Akismet.
     *
     * @param array $data
     * @return HttpResponse
     */
    public function submitHam(array $data): HttpResponse
    {
        $body = http_build_query($data);
        return $this->post("/submit-spam", $body);
    }
}
