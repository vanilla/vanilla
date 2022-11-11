<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Firebase\JWT\JWT;
use Garden\Web\RequestInterface;
use Vanilla\CurrentTimeStamp;

/**
 * An encoded role token (JWT) is issued for authenticating and authorizing access to API v2 endpoints where the
 * response content depends on the requestor's role(s) but doesn't depend on the requestor's identity.
 * Role tokens enable cacheable API v2 endpoint responses for users that share a set of roles for those
 * endpoints that meet the criteria specified above. Role tokens are not intended to be persisted and are used
 * only for authenticating where the expiration period is fairly short (<= ~ 5min)
 */
class RoleToken
{
    //region Constants
    const SIGNING_ALGORITHM = "HS512";
    const PAYLOAD_CLAIM_ROLE_IDS = "roleIDs";

    const SECRET_MIN_LENGTH = 10;
    //endregion

    //region Properties
    /** @var int[] $roleIDs */
    private $roleIDs;

    /** @var string $secret */
    private $secret;

    /** @var \DateInterval $window */
    private $window;

    /** @var \DateTimeImmutable $expires */
    private $expires;

    /** @var \DateInterval $rolloverWithin */
    private $rolloverWithin;

    /** @var RequestInterface $requestor */
    private $requestor;

    /** @var array $decoded */
    private $decoded;

    //endregion

    //region Constructors
    /**
     * Private constructor - obtain new instances via static factory methods
     *
     * @param string $secret
     */
    private function __construct(string $secret)
    {
        $this->secret = $secret;
    }
    //endregion

    //region Static Factory Methods
    /**
     * Factory method used to instantiate a new role token instance that is empty apart from its signing secret
     *
     * @param string $secret Signing secret for encoding and decoding role token
     * @return RoleToken
     */
    public static function withSecret(string $secret): RoleToken
    {
        $minLength = self::SECRET_MIN_LENGTH;
        if (strlen($secret) < $minLength) {
            throw new \LengthException("Secret must be at least {$minLength} characters long");
        }
        return new self($secret);
    }

    /**
     * Get a role token used for encoding into a JWT
     *
     * @param string $secret Signing secret for encoding and decoding role token
     * @param int $windowSec Amount of time used to partition time into blocks as wide as the specified
     * window, relative to Unix epoch
     * @param int|null $rolloverWithinSec Optional, Amount of time relative to the start of the next window
     * @return RoleToken
     */
    public static function forEncoding(string $secret, int $windowSec, ?int $rolloverWithinSec = null): RoleToken
    {
        $roleToken = static::withSecret($secret);
        if ($windowSec <= 0) {
            throw new \DomainException("Cannot set a zero or negative length window");
        }
        if (isset($rolloverWithinSec) && $rolloverWithinSec < 0) {
            throw new \DomainException("Cannot set a negative length rollover");
        }
        if (($rolloverWithinSec ?? 0) >= $windowSec) {
            throw new \DomainException("Rollover must be less than window length");
        }
        $roleToken->window = new \DateInterval("PT{$windowSec}S");
        if (isset($rolloverWithinSec)) {
            $roleToken->rolloverWithin = new \DateInterval("PT{$rolloverWithinSec}S");
        }
        return $roleToken;
    }
    //endregion

    //region Public instance methods
    /**
     * Encode and sign the content of the role token as a JWT.
     * Note that if the RoleToken was not instantiated with a factory method that specified
     * the time window for which this token is valid, the encoded token will contain parameters
     * which, when decoded, will indicate that the token is invalid.
     *
     * @return string Signed encoded role token
     */
    public function encode(): string
    {
        $now = CurrentTimeStamp::getDateTime();
        $this->expires = isset($this->window)
            ? CurrentTimeStamp::toNextWindow($this->window, $this->rolloverWithin)
            : $now;
        $notBefore = isset($this->window) ? CurrentTimeStamp::toWindowStart($this->window) : $now;
        // Note that we're omitting a set of registered JWT claims primarily for caching/reusability
        // see: https://datatracker.ietf.org/doc/html/rfc7519#section-4.1
        // - 'sub' (Subject)
        // - 'iat' (Issued At)
        // - 'aud' (Audience)
        // - 'jti' (JWT ID)
        $payload = [
            "exp" => $this->expires->getTimestamp(),
            "nbf" => $notBefore->getTimestamp(),
            self::PAYLOAD_CLAIM_ROLE_IDS => $this->getRoleIDs(),
        ];
        if (isset($this->requestor)) {
            $issuer =
                "{$this->requestor->getScheme()}://" .
                rtrim($this->requestor->getHost(), "/") .
                "/" .
                trim($this->requestor->getPath(), "/");
            $payload = array_merge(["iss" => $issuer], $payload);
        }

        $jwt = JWT::encode($payload, $this->secret, self::SIGNING_ALGORITHM);
        return $jwt;
    }

    /**
     * Decode the provided encoded token and populate various properties using payload values
     *
     * @param string $encodedToken Encoded role token as returned by encode() method
     * @return $this
     *
     * @throws \InvalidArgumentException Provided JWT was empty.
     * @throws \UnexpectedValueException Provided JWT was invalid.
     * @throws \Firebase\JWT\SignatureInvalidException Provided JWT failed signature verification.
     * @throws \Firebase\JWT\BeforeValidException Attempting to use JWT before it's eligible as defined by 'nbf'.
     * @throws \Firebase\JWT\BeforeValidException Attempting to use JWT before it's been created as defined by 'iat'.
     * @throws \Firebase\JWT\ExpiredException Provided JWT has since expired, as defined by the 'exp' claim.
     */
    public function decode(string $encodedToken): RoleToken
    {
        $this->decoded = (array) JWT::decode($encodedToken, $this->secret, [self::SIGNING_ALGORITHM]);
        if (!empty($this->decoded[self::PAYLOAD_CLAIM_ROLE_IDS])) {
            $this->setRoleIDs($this->decoded[self::PAYLOAD_CLAIM_ROLE_IDS]);
        }
        return $this;
    }
    //endregion

    //region Public property accessor methods

    /**
     * Get datetime when this token is to expire. This is only set when the role token is encoded.
     *
     * @return \DateTimeInterface
     */
    public function getExpires(): \DateTimeInterface
    {
        return $this->expires ?? CurrentTimeStamp::getDateTime();
    }

    /**
     * Get the associative array returned when decoding the JWT
     *
     * @return array|null
     */
    public function getDecoded(): ?array
    {
        return $this->decoded;
    }

    /**
     * Get the request for which the role token was created.
     *
     * @return RequestInterface
     */
    public function getRequestor(): RequestInterface
    {
        return $this->requestor;
    }

    /**
     * Fluent interface setter for the request for which this role token was created.
     *
     * @param RequestInterface $request
     */
    public function setRequestor(RequestInterface $request): RoleToken
    {
        $this->requestor = $request;
        return $this;
    }

    /**
     * Get the set of role IDs for this token
     *
     * @return array|string[]
     */
    public function getRoleIDs(): array
    {
        return $this->roleIDs ?? [];
    }

    /**
     * Fluent interface for setting a set of role IDs for the role token
     *
     * @param array $roleIDs Set of role IDs to include in this role token
     * @return $this
     * @throws \LengthException Empty role ID set.
     * @throws \InvalidArgumentException Array passed includes one or more invalid entries.
     */
    public function setRoleIDs(array $roleIDs): RoleToken
    {
        if (empty($roleIDs)) {
            throw new \LengthException("Must specify at least one role ID");
        }

        $noncompliantRoleIDs = array_filter($roleIDs, function ($roleID) {
            return !is_numeric($roleID) || intval($roleID) <= 0;
        });

        if (!empty($noncompliantRoleIDs)) {
            throw new \InvalidArgumentException("Each Role ID must be a positive numeric value");
        }
        $this->roleIDs = array_map(function ($roleID) {
            return intval($roleID);
        }, $roleIDs);

        return $this;
    }
    //endregion
}
