<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla;

use Garden\Http\HttpClient;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class reCaptchaVerification
 */
class ReCaptchaVerification {

    const RECAPTCHA_V3_URL = "https://www.google.com/recaptcha/api/siteverify";

    /** @var HttpClient */
    private $httpClient;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * ReCaptchaVerification constructor.
     *
     * @param HttpClient $httpClient
     * @param ConfigurationInterface $config
     */
    public function __construct(HttpClient $httpClient, ConfigurationInterface $config) {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * Verify is a challenge is valid, using siteVerify endpoint.
     *
     * @param string $responseToken
     * @return bool
     */
    public function siteVerifyV3(string $responseToken = ''): bool {
        $body = [
            "secret" => $this->config->get("RecaptchaV3.PrivateKey", ''),
            "response" => $responseToken,
        ];
        $reCaptchaResponse = $this->httpClient->post(self::RECAPTCHA_V3_URL, $body)->getBody();
        $reCaptchaResponse= $reCaptchaResponse["success"] ?? false;

        return $reCaptchaResponse;
    }
}
