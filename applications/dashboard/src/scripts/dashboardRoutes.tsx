/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getDashboardUserManagementRoutes } from "@dashboard/users/userManagement/UserManagementRoutes";
import RouteHandler from "@library/routing/RouteHandler";
import { getAutomationRulesRoutes } from "@dashboard/automationRules/AutomationRules.routes";

const RoleApplicationsRoute = new RouteHandler(
    () => import("@dashboard/roleRequests/pages/RoleApplicationsPage"),
    "/manage/requests/role-applications",
    () => "/manage/requests/role-applications",
);

const AuditLogsRoute = new RouteHandler(
    () => import("@dashboard/auditLogs/AuditLogsPage").then((imp) => imp.AuditLogsPage),
    "/dashboard/settings/audit-logs",
    () => "/dashboard/settings/audit-logs",
);

export function getDashboardRoutes() {
    return [
        RoleApplicationsRoute.route,
        ...getDashboardUserManagementRoutes(),
        ...getAutomationRulesRoutes(),
        AuditLogsRoute.route,
    ];
}
