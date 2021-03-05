<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Firebase\JWT\JWT;
use Garden\Web\RequestInterface;
use UnexpectedValueException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Utility\ArrayUtils;

/**
 * Utility class for handling system tokens.
 */
class SystemTokenUtils {

    public const JWT_ALGO = "HS512";

    public const CLAIM_REQUEST_BODY = "body";

    public const CLAIM_REQUEST_QUERY = "query";

    public const TOKEN_TTL = 30;

    /** @var string */
    private $secret;

    /**
     * SystemTokenModel constructor.
     *
     * @param string $secret
     */
    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    /**
     * Decode a system JWT token, using a request to provide additional context.
     *
     * @param string $jwt
     * @param RequestInterface $context
     * @return array
     */
    public function decode(string $jwt, RequestInterface $context): array {
        $payload = JWT::decode($jwt, $this->secret, [self::JWT_ALGO]);
        $payload = ArrayUtils::objToArrayRecursive($payload);

        $requestQuery = $context->getQuery() ?? [];
        $payloadQuery = $payload[self::CLAIM_REQUEST_QUERY] ?? [];
        if (array_diff_key($requestQuery, $payloadQuery) || array_diff_key($payloadQuery, $requestQuery)) {
            throw new UnexpectedValueException("Token query does not match request.");
        }

        foreach ($requestQuery as $param => $value) {
            if ($payloadQuery[$param] != $value) {
                throw new UnexpectedValueException("Token query value differs from request.");
            }
        }

        return $payload;
    }

    /**
     * Generate a system JWT token.
     *
     * @param int $userID
     * @param array|null $body
     * @param array|null $query
     * @return string
     */
    public function encode(int $userID, ?array $body = null, ?array $query = null): string {
        $timestamp = CurrentTimeStamp::get();
        $payload = [
            "exp" => $timestamp + self::TOKEN_TTL,
            "iat" => $timestamp,
            "sub" => $userID
        ];

        if (is_array($body)) {
            $payload[self::CLAIM_REQUEST_BODY] = $body;
        }
        if (is_array($query)) {
            $payload[self::CLAIM_REQUEST_QUERY] = $query;
        }

        return JWT::encode($payload, $this->secret, self::JWT_ALGO);
    }

    /**
     * Set the configured secret.
     *
     * @return string
     */
    public function getSecret(): string {
        return $this->secret;
    }

    /**
     * Get the configured secret.
     *
     * @param string $secret
     */
    public function setSecret(string $secret): void {
        $this->secret = $secret;
    }
}
