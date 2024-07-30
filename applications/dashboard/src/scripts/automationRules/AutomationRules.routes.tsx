/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";

const AutomationRulesPageRoute = new RouteHandler(
    () => import("@dashboard/automationRules/pages/AutomationRulesPage"),
    "/settings/automation-rules",
    () => "/settings/automation-rules",
);

const AutomationRuleAddEditRoute = new RouteHandler(
    () => import("@dashboard/automationRules//pages/AutomationRulesAddEditPage"),

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

const EscalationRulesPageRoute = new RouteHandler(
    () => import("@dashboard/automationRules/pages/EscalationRulesPage"),
    "/dashboard/content/escalation-rules",
    () => "/dashboard/content/escalation-rules",
);

const EscalationRuleAddEditRoute = new RouteHandler(
    () => import("@dashboard/automationRules/pages/AutomationRulesAddEditPage"),

    ["/dashboard/content/escalation-rules/:automationRuleID/edit", "/dashboard/content/escalation-rules/add"],
    (params: { automationRuleID?: number }) =>
        params.automationRuleID != null
            ? `/dashboard/content/escalation-rules/${params.automationRuleID}/edit`
            : "/dashboard/content/escalation-rules/add/",
);

export function getAutomationRulesRoutes() {
    return [
        AutomationRulesPageRoute.route,
        AutomationRuleAddEditRoute.route,
        AutomationRulesHistoryRoute.route,
        EscalationRulesPageRoute.route,
        EscalationRuleAddEditRoute.route,
    ];
}
