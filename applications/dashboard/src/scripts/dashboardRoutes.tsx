/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import RouteHandler from "@library/routing/RouteHandler";

const RoleApplicationsRoute = new RouteHandler(
    () => import("@dashboard/roleRequests/pages/RoleApplicationsPage"),
    "/manage/requests/role-applications",
    () => "/manage/requests/role-applications",
);

export function getDashboardRoutes() {
    return [RoleApplicationsRoute.route];
}
