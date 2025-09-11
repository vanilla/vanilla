/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getDashboardUserManagementRoutes } from "@dashboard/users/userManagement/UserManagementRoutes";
import RouteHandler from "@library/routing/RouteHandler";
import { getAutomationRulesRoutes } from "@dashboard/automationRules/AutomationRules.routes";
import { getPostTypeSettingsRoutes } from "@dashboard/postTypes/postTypeRoutes";
import { getTaggingSettingsRoutes } from "@dashboard/tagging/taggingRoutes";

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
        ...getPostTypeSettingsRoutes(),
        ...getTaggingSettingsRoutes(),
        AuditLogsRoute.route,
    ];
}
