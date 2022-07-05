<?php
/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Utils;

// TODO: Replace with GardenHttp

/**
 * Http utilities for scripts.
 */
final class ShellHttp
{
    /** @var SimpleScriptLogger */
    private $logger;

    /** @var string Path of the cookie jar. */
    private $cookie_jar;

    /**
     * ShellHttp constructor.
     *
     * @param SimpleScriptLogger $logger
     */
    public function __construct(SimpleScriptLogger $logger)
    {
        $this->logger = $logger;
        $this->cookie_jar = $this->getCookieJarFile();
    }

    /**
     * Returns a filename for the cookiejar.
     *
     * @return string
     */
    private function getCookieJarFile(): string
    {
        $cwd = getcwd();
        $path = "$cwd/vanilla-clone.cookiejar";
        if (!is_writable($cwd)) {
            $this->logger->error("Can't write to $path. Please make sure this user has write permissions.");
            exit();
        }
        return $path;
    }

    /**
     * Returns common curl options for all requests.
     *
     * @return array
     */
    private function getBaseCurlOpts(): array
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_COOKIEJAR => $this->cookie_jar,
            CURLOPT_COOKIEFILE => $this->cookie_jar,
        ];
    }

    /**
     * Gets info from the curl handle and returns a response array.
     *
     * @param resource $ch
     * @return array
     */
    private function getResponse($ch): array
    {
        $data = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($data, $header_size);
        return [
            "body" => $body,
            "httpCode" => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            "url" => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            "request" => curl_getinfo($ch, CURLINFO_HEADER_OUT),
        ];
    }

    /**
     * Sends a curl request using a url and curl options.
     *
     * @param string $url
     * @param array $curlopts
     * @return array
     */
    public function sendCurlRequest(string $url, array $curlopts = []): array
    {
        $ch = curl_init($url);
        $opts = $this->getBaseCurlOpts() + $curlopts;
        curl_setopt_array($ch, $opts);
        $response = $this->getResponse($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * Send a POST request to the url. "form" post fields can be specified in the options
     *
     * @param string $url
     * @param array $opts
     * @return array
     */
    public function post(string $url, array $opts = []): array
    {
        return $this->sendCurlRequest($url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $opts["form"],
        ]);
    }

    /**
     * Send a GET request to the url.
     *
     * @param string $url
     * @param array $opts
     * @return array
     */
    public function get(string $url, array $opts = []): array
    {
        return $this->sendCurlRequest($url);
    }
}
