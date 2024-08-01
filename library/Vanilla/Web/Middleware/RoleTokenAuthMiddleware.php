<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Web\Data;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\MethodNotAllowedException;
use Garden\Web\RequestInterface;
use Vanilla\Permissions;
use Vanilla\Web\CacheControlConstantsInterface;
use Vanilla\Web\RoleToken;
use Vanilla\Web\RoleTokenAuthTrait;
use Vanilla\Web\RoleTokenFactory;

/**
 * Middleware used to authenticate via role token provided in request query parameters
 */
class RoleTokenAuthMiddleware
{
    use RoleTokenAuthTrait;

    //region Constants
    /**
     * @var string Name of URL query string parameter used to hold role token
     * @see RoleTokenAuthTrait::getRoleTokenParamName() for method that most classes should use to access
     * this value, as traits cannot define constants
     */
    const ROLE_TOKEN_QUERY_PARAM_NAME = "role-token";
    //endregion

    //region Properties
    /** @var \Gdn_Session $session */
    private $session;

    /** @var \PermissionModel $permissionModel */
    private $permissionModel;

    /** @var RoleTokenFactory $roleTokenFactory */
    private $roleTokenFactory;
    //endregion

    //region Constructor
    /**
     * DI Constructor
     *
     * @param \Gdn_Session $session
     * @param \PermissionModel $permissionModel
     * @param RoleTokenFactory $roleTokenFactory
     */
    public function __construct(
        \Gdn_Session $session,
        \PermissionModel $permissionModel,
        RoleTokenFactory $roleTokenFactory
    ) {
        $this->session = $session;
        $this->permissionModel = $permissionModel;
        $this->roleTokenFactory = $roleTokenFactory;
    }
    //endregion

    //region Magic methods
    /**
     * Invoke the middleware on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     * @throws ForbiddenException Invalid role token or used with other auth mechanism or sent on insecure channel.
     * @throws MethodNotAllowedException Non-idempotent method specified.
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        if (static::isRoleTokenProvided($request)) {
            $this->throwIfInvalidRequest($request);

            // Exceptions thrown when role token is invalid
            $roleToken = $this->extractRoleToken($request);
            $this->applyRoleToken($roleToken);
            $schemaAdd = static::getRoleTokenAuthSchema();
            $query = $request->getQuery();
            $query["schemaAdd"] = $schemaAdd;
            $request->setQuery($query);
            $response = Data::box($next($request));

            $response->setHeader(
                CacheControlConstantsInterface::HEADER_CACHE_CONTROL,
                CacheControlConstantsInterface::PUBLIC_CACHE
            );
            $response->setMeta(CacheControlConstantsInterface::META_NO_VARY, true);

            return $response;
        } else {
            return $next($request);
        }
    }
    //endregion

    //region Protected methods
    /**
     * Validate the request for which role token auth was requested to be applied,
     * and throw an exception if the request is invalid.
     *
     * @param RequestInterface $request
     * @throws MethodNotAllowedException Non-idempotent method specified.
     * @throws ForbiddenException Request not sent over secure channel.
     * @throws ForbiddenException Role token auth is specified in addition to another authentication mechanism.
     */
    protected function throwIfInvalidRequest(RequestInterface $request): void
    {
        if (strtolower($request->getScheme()) !== "https") {
            throw new ForbiddenException("Role token auth only applies to secure requests");
        }
        switch (strtoupper($request->getMethod())) {
            case "GET":
            case "HEAD":
            case "OPTIONS":
                break;
            default:
                throw new MethodNotAllowedException("Role token auth only applies to idempotent methods", [
                    "GET",
                    "HEAD",
                    "OPTIONS",
                ]);
        }
        if ($this->session->isValid()) {
            throw new ForbiddenException("Cannot utilize role token auth with other auth mechanisms");
        }
    }

    /**
     * Extract the encoded role token from the request, attempt to decode the encoded JWT
     * into a role token object and validate its contents.
     *
     * @param RequestInterface $request Request that contains encoded role token
     * @return RoleToken
     * @throws ForbiddenException Exception thrown during JWT decode.
     * @throws ForbiddenException Role token contains no roles.
     */
    protected function extractRoleToken(RequestInterface &$request): RoleToken
    {
        $emptyRoleToken = $this->roleTokenFactory->forDecoding();
        $query = $request->getQuery();
        $encodedJwt = $query[self::getRoleTokenParamName()];
        try {
            $roleToken = $emptyRoleToken->decode($encodedJwt);
        } catch (\Exception $e) {
            throw new ForbiddenException("Error decoding role token: {$e->getMessage()}", $request->getQuery());
        }

        if (empty($roleToken->getRoleIDs())) {
            throw new ForbiddenException("Error decoding role token: No Roles assigned", $request->getQuery());
        }

        unset($query[self::getRoleTokenParamName()]);
        $request->setQuery($query);

        return $roleToken;
    }

    /**
     * Apply the role token for use by endpoints which support role token authentication and authorization
     *
     * @param RoleToken $roleToken Role token contained in request to be used when generating response
     */
    protected function applyRoleToken(RoleToken $roleToken): void
    {
        //apply permissions derived from roles to session
        $roleIDs = $roleToken->getRoleIDs();
        $currentPermissions = $this->session->getPermissions();
        $permissions = $this->permissionModel->getPermissionsByRole(...array_values($roleIDs));
        $this->session->addPermissions($permissions);

        //apply role token permissions ban to the session
        $currentPermissions->addBan(Permissions::BAN_ROLE_TOKEN, [
            "msg" => "A role token cannot be used to authenticate to this endpoint",
            "code" => 403,
        ]);
    }
    //endregion
}
