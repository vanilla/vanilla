/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import PageLoader from "@library/routing/PageLoader";
import RouteHandler from "@library/routing/RouteHandler";

export const DeveloperProfileListRoute = new RouteHandler(
    () => import("@dashboard/developer/pages/DeveloperProfileListPage"),
    "/settings/developer/profiles",
    () => "/settings/developer/profiles",
    PageLoader,
);

export const DeveloperProfileDetailRoute = new RouteHandler(
    () => import("@dashboard/developer/pages/DeveloperProfileDetailPage"),
    "/settings/developer/profiles/:profileID",
    (profileID: number) => `/settings/developer/profiles/${profileID}`,
    PageLoader,
);

export function getDeveloperRoutes() {
    return [DeveloperProfileListRoute.route, DeveloperProfileDetailRoute.route];
}
