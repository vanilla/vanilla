<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\RequestInterface;
use Gdn;
use Vanilla\Exception\PermissionException;

/**
 * Middleware to permission check allowed output format extensions.
 */
class ApiExtensionMiddleware
{
    private $permissionedExtensions = [
        "csv" => "exports.manage",
    ];

    /**
     * Validate that the session user has the requisite permission to get the requested output format.
     * Throws a permission error if the user lacks the permission.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return mixed
     * @throws PermissionException
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        $path = $request->getPath();
        foreach ($this->permissionedExtensions as $extension => $permission) {
            if (str_ends_with(strtolower($path), "." . $extension)) {
                $hasPermission = Gdn::session()->checkPermission($permission);
                if (!$hasPermission) {
                    throw new PermissionException($permission);
                }
            }
        }
        return $next($request);
    }
}
