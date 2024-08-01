/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";

const AutomationRulesListRoute = new RouteHandler(
    () => import("@dashboard/automationRules/AutomationRulesList"),
    "/settings/automation-rules",
    () => "/settings/automation-rules",
);

const AutomationRuleAddEditRoute = new RouteHandler(
    () => import("@dashboard/automationRules/AutomationRulesAddEditRule"),

    ["/settings/automation-rules/:automationRuleID/edit", "/settings/automation-rules/add"],
    (params: { automationRuleID?: number }) =>
        params.automationRuleID != null
            ? `/settings/automation-rules/${params.automationRuleID}/edit`
            : "/settings/automation-rules/add/",
);

const AutomationRulesHistoryRoute = new RouteHandler(
    () => import("@dashboard/automationRules/history/AutomationRulesHistory"),
    "/settings/automation-rules/history",
    () => "/settings/automation-rules/history",
);

export function getAutomationRulesRoutes() {
    return [AutomationRulesListRoute.route, AutomationRuleAddEditRoute.route, AutomationRulesHistoryRoute.route];
}
