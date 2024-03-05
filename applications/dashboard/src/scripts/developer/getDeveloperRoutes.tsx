/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminHeader from "@dashboard/components/AdminHeader";
import Loader from "@library/loaders/Loader";
import PageLoader from "@library/routing/PageLoader";
import RouteHandler from "@library/routing/RouteHandler";

function DeveloperRoutePageLoader() {
    return (
        <>
            <AdminHeader />
            <Loader />
        </>
    );
}

export const DeveloperProfileListRoute = new RouteHandler(
    () => import("@dashboard/developer/pages/DeveloperProfileListPage"),
    "/settings/developer/profiles",
    () => "/settings/developer/profiles",
    DeveloperRoutePageLoader,
);

export const DeveloperProfileDetailRoute = new RouteHandler(
    () => import("@dashboard/developer/pages/DeveloperProfileDetailPage"),
    "/settings/developer/profiles/:profileID",
    (profileID: number) => `/settings/developer/profiles/${profileID}`,
    DeveloperRoutePageLoader,
);

export function getDeveloperRoutes() {
    return [DeveloperProfileListRoute.route, DeveloperProfileDetailRoute.route];
}
