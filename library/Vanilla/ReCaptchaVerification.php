<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
namespace Vanilla;


use Garden\Http\HttpClient;

/**
 * Class reCaptchaVerification
 */
class ReCaptchaVerification {

    CONST RECAPTCHA_V3_URL = "https://www.google.com/recaptcha/api/siteverify";

    /** @var HttpClient */
    private $httpClient;

    /**
     * reCaptchaVerification constructor.
     */
    public function __construct() {
        $this->httpClient = new HttpClient();
    }

    /**
     * Verify is a challenge is valid, using siteVerify endpoint.
     *
     * @param string $privateKey
     * @param string $responseToken
     * @return bool
     */
    public function siteVerify(string $privateKey = '', string $responseToken = ''):bool {
        $body = [
            "secret" => $privateKey,
            "response" => $responseToken,
        ];
        $reCaptchaResponse = $this->httpClient->post(self::RECAPTCHA_V3_URL, $body)->getBody();
        $reCaptchaResponse= $reCaptchaResponse["success"] ?? false;

        return $reCaptchaResponse;
    }
}
