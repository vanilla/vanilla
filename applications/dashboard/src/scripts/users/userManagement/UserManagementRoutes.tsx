/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loader from "@library/loaders/Loader";
import RouteHandler from "@library/routing/RouteHandler";

const UserManagementDashboardIndexRoute = new RouteHandler(
    () =>
        import(
            /* webpackChunkName: "dashboard/users/userManagement" */ "@dashboard/users/userManagement/UserManagementPage"
        ),
    "/dashboard/user",
    () => "/dashboard/user",
    Loader,
);

const UserManagementDashboardBrowseRoute = new RouteHandler(
    () =>
        import(
            /* webpackChunkName: "dashboard/users/userManagement/browse" */ "@dashboard/users/userManagement/UserManagementPage"
        ),
    "/dashboard/user/browse",
    () => "/dashboard/user/browse",
    Loader,
);
const UserManagementIndexRoute = new RouteHandler(
    () => import(/* webpackChunkName: "users/userManagement" */ "@dashboard/users/userManagement/UserManagementPage"),
    "/user",
    () => "/user",
    Loader,
);

const UserManagementBrowseRoute = new RouteHandler(
    () =>
        import(
            /* webpackChunkName: "users/userManagement/browse" */ "@dashboard/users/userManagement/UserManagementPage"
        ),
    "/user/browse",
    () => "/user/browse",
    Loader,
);

export function getDashboardUserManagementRoutes() {
    return [
        UserManagementDashboardIndexRoute.route,
        UserManagementDashboardBrowseRoute.route,
        UserManagementIndexRoute.route,
        UserManagementBrowseRoute.route,
    ];
}
