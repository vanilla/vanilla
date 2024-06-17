/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loader from "@library/loaders/Loader";
import RouteHandler from "@library/routing/RouteHandler";

const ReportsListPageRoute = new RouteHandler(
    () => import("@dashboard/moderation/ReportsPage"),
    "/dashboard/content/reports",
    () => "/dashboard/content/reports",
    Loader,
);

const TriageListPageRoute = new RouteHandler(
    () => import("@dashboard/moderation/TriagePage"),
    "/dashboard/content/triage",
    () => "/dashboard/content/triage",
    Loader,
);

const TriageDetailPageRoute = new RouteHandler(
    () => import("@dashboard/moderation/TriageDetailPage"),
    `/dashboard/content/triage/:recordID`,
    (recordID: number) => `/dashboard/content/triage/${recordID}`,
    Loader,
);

const EscalationsListPageRoute = new RouteHandler(
    () => import("@dashboard/moderation/EscalationsPage"),
    "/dashboard/content/escalations",
    () => "/dashboard/content/escalations",
    Loader,
);

const EscalationsDetailPageRoute = new RouteHandler(
    () => import("@dashboard/moderation/EscalationsDetailPage"),
    `/dashboard/content/escalations/:escalationID`,
    (escalationID: number) => `/dashboard/content/triage/${escalationID}`,
    Loader,
);

export function getCommunityManagementRoutes() {
    return [
        ReportsListPageRoute.route,
        TriageListPageRoute.route,
        TriageDetailPageRoute.route,
        EscalationsListPageRoute.route,
        EscalationsDetailPageRoute.route,
    ];
}
