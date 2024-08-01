<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Web\Exception\ServerException;
use Gdn_Session;
use Garden\BasePathTrait;
use Garden\Web\RequestInterface;
use Vanilla\Permissions;
use Vanilla\Web\SystemTokenUtils;

/**
 * Middleware for verifying system tokens, rewriting requests based on their payloads and granting the system permission.
 */
class SystemTokenMiddleware
{
    public const AUTH_CONTENT_TYPE = "application/system+jwt";

    use BasePathTrait;

    /** @var Gdn_Session */
    private $session;

    /** @var SystemTokenUtils */
    private $tokenUtils;

    /**
     * SystemTokenMiddleware constructor.
     *
     * @param string $basePath
     * @param SystemTokenUtils $tokenUtils
     * @param Gdn_Session $session
     */
    public function __construct(string $basePath, SystemTokenUtils $tokenUtils, Gdn_Session $session)
    {
        $this->setBasePath($basePath);
        $this->session = $session;
        $this->tokenUtils = $tokenUtils;
    }

    /**
     * Invoke the middleware on a request.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        if ($this->inBasePath($request->getPath()) && $request->getHeader("Content-Type") === self::AUTH_CONTENT_TYPE) {
            if (empty($this->tokenUtils->getSecret())) {
                throw new ServerException("System token secret has not been configured.");
            }

            $this->updateRequest($request);
        }

        $response = $next($request);
        return $response;
    }

    /**
     * Validate a JWT token in the request body and update the request based on its payload.
     *
     * @param RequestInterface $request
     */
    private function updateRequest(RequestInterface $request): void
    {
        $payload = $this->tokenUtils->decode($request->getRawBody(), $request);

        $userID = $payload["sub"];
        if (is_int($userID)) {
            $this->session->start($userID, false, false);
        }

        $this->session->setPermission(Permissions::PERMISSION_SYSTEM, true);

        $body = $payload[SystemTokenUtils::CLAIM_REQUEST_BODY] ?? null;
        if (is_array($body)) {
            $request->setBody($body);
        }

        $this->session->validateTransientKey(true);
    }
}
