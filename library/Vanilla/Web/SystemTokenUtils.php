<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Garden\Web\Exception\ClientException;
use Garden\Web\RequestInterface;
use Gdn_Session;
use UnexpectedValueException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Utility\ArrayUtils;

/**
 * Utility class for handling system tokens.
 */
class SystemTokenUtils
{
    public const JWT_ALGO = "HS512";

    public const CLAIM_REQUEST_BODY = "body";

    public const CLAIM_REQUEST_QUERY = "query";
    public const CLAIM_REQUEST_SERVICE = "svc";

    public const TOKEN_TTL = 60 * 60 * 24;

    /** @var string */
    private $secret;

    /** @var Gdn_Session */
    private $session;

    /**
     * SystemTokenModel constructor.
     *
     * @param string $secret
     */
    public function __construct(string $secret, Gdn_Session $session)
    {
        $this->secret = $secret;
        $this->session = $session;
    }

    /**
     * Decode a system JWT token, using a request to provide additional context.
     *
     * @param string $jwt
     * @param ?RequestInterface $context
     * @return array
     */
    public function decode(string $jwt, ?RequestInterface $context): array
    {
        $payload = JWT::decode($jwt, new Key($this->secret, self::JWT_ALGO));
        $payload = ArrayUtils::objToArrayRecursive($payload);

        if ($context === null) {
            return $payload;
        }
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
     * Given a vnla_sys token, validate it and return the name of the calling service.
     *
     * @param string $token
     *
     * @return string
     * @throws ClientException
     */
    public function authenticateDynamicSystemTokenService(string $token): string
    {
        $pieces = explode(".", $token);
        $firstPiece = array_shift($pieces);
        if ($firstPiece !== "vnla_sys") {
            throw new ClientException("Invalid system token.", 401);
        }

        $token = implode(".", $pieces);
        try {
            $payload = $this->decode($token, null);
        } catch (\Exception $exception) {
            throw new ClientException("Invalid system token - {$exception->getMessage()}", 401, [], $exception);
        }
        $service = $payload[self::CLAIM_REQUEST_SERVICE] ?? null;
        if ($service === null) {
            throw new ClientException("Dynamic system token must declare a service.", 401);
        }

        return $service;
    }

    /**
     * Generate a system JWT token.
     *
     * @param array|null $body
     * @param array|null $query
     * @param string|null $service
     * @return string
     */
    public function encode(?array $body = null, ?array $query = null, ?string $service = null): string
    {
        $timestamp = CurrentTimeStamp::get();
        $payload = [
            "exp" => $timestamp + self::TOKEN_TTL,
            "iat" => $timestamp,
        ];

        if (is_array($body)) {
            $payload[self::CLAIM_REQUEST_BODY] = $body;
        }
        if (is_array($query)) {
            $payload[self::CLAIM_REQUEST_QUERY] = $query;
        }

        if ($service !== null) {
            $payload[self::CLAIM_REQUEST_SERVICE] = $service;
        } else {
            $payload["sub"] = $this->session->UserID;
        }

        return JWT::encode($payload, $this->secret, self::JWT_ALGO);
    }

    /**
     * Set the configured secret.
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Get the configured secret.
     *
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }
}
