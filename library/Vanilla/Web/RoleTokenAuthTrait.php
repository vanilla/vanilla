<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Schema\Schema;
use Garden\Web\RequestInterface;
use Vanilla\Web\Middleware\RoleTokenAuthMiddleware;

/**
 * Shared functionality related to authentication / authorization via role token
 */
trait RoleTokenAuthTrait
{
    //region Public Static Methods
    /**
     * Get name of URL query string param where role token is expected
     *
     * @return string
     */
    final public static function getRoleTokenParamName(): string
    {
        return RoleTokenAuthMiddleware::ROLE_TOKEN_QUERY_PARAM_NAME;
    }

    /**
     * Determine whether the provided request contains an encoded role token to use for authentication / authorization.
     * This does not check the validity or contents of the role token, nor does this decode the role token,
     * but simply checks whether the request has a field corresponding to where a role token is expected to be found.
     *
     * @param RequestInterface $request Request being processed
     * @return bool True if encoded role token detected within request, false otherwise
     */
    public static function isRoleTokenProvided(RequestInterface $request): bool
    {
        $queryParams = $request->getQuery();
        return !empty($queryParams) && array_key_exists(self::getRoleTokenParamName(), $queryParams);
    }

    /**
     * Get the schema specific to the role token auth query parameter
     *
     * @return Schema
     */
    public static function getRoleTokenAuthSchema(): Schema
    {
        $paramName = self::getRoleTokenParamName();
        return Schema::parse(["{$paramName}:s?"]);
    }
    //endregion
}
