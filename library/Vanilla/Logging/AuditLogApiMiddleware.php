<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Dashboard\Events\AccessDeniedEvent;
use Vanilla\Dashboard\Events\DashboardApiAccessEvent;
use Vanilla\Exception\PermissionException;
use Vanilla\Http\InternalRequest;

/**
 * Middleware for audit logging of API requests from the dashboard.
 */
class AuditLogApiMiddleware
{
    /**
     * @param RequestInterface $request
     * @param callable $next
     *
     * @return Data
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        if ($request instanceof InternalRequest) {
            return $next($request);
        }
        $dashboardApiAccessEvent = DashboardApiAccessEvent::tryFromHeaders($request);
        if ($dashboardApiAccessEvent !== null) {
            AuditLogger::log($dashboardApiAccessEvent);
        }

        $response = $next($request);
        return $response;
    }
}
