/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import RouteHandler from "@library/routing/RouteHandler";
import { ILayoutDetails, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { slugify } from "@vanilla/utils";
import ModalLoader from "@library/modal/ModalLoader";
import AppearanceRoutePageLoader from "../components/AppearanceRoutePageLoader";
import { getLayoutTypeSettingsUrl } from "../components/layoutViewUtils";

type LayoutFragment = {
    layoutID: ILayoutDetails["layoutID"];
    name: ILayoutDetails["name"];
    layoutViewType: ILayoutDetails["layoutViewType"];
};

export const AppearanceRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/AppearancePage"),
    "/appearance",
    () => "/appearance",
    AppearanceRoutePageLoader,
);

export const LegacyLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/LegacyLayoutsPage"),
    "/appearance/layouts/:layoutViewType/legacy",
    (layoutViewType: LayoutViewType) => getLayoutTypeSettingsUrl(layoutViewType),
    AppearanceRoutePageLoader,
);

export const BrandingPageRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/BrandingAndSEOPage"),
    "/appearance/branding",
    () => "/appearance/branding",
    AppearanceRoutePageLoader,
);

export const ManageIconsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/ManageIconsPage"),
    "/appearance/icons",
    () => "/appearance/icons",
    AppearanceRoutePageLoader,
);

// no adminlayout?
export const LayoutEditorRoute = new RouteHandler(
    () => import("@dashboard/layout/pages/LayoutEditorPage"),
    [`/appearance/layouts/:layoutViewType/:layoutID(\\w+)(-[^/]+)?/edit`, "/appearance/layouts/:layoutViewType/add"],
    (layout: Partial<LayoutFragment> & { isCopy?: boolean }) => {
        if (layout.layoutID) {
            let slug = layout.layoutID;
            if (layout.name) {
                slug += "-" + slugify(layout.name);
            }
            return `/appearance/layouts/${layout.layoutViewType}/${slug}/edit` + (layout.isCopy ? "?copy=true" : "");
        } else {
            return `/appearance/layouts/${layout.layoutViewType}/add`;
        }
    },
    ModalLoader,
);

export const LayoutOverviewRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/LayoutOverviewPage"),
    `/appearance/layouts/:layoutViewType/:layoutID(\\w+)(-[^/]+)?`,
    (layout: LayoutFragment) =>
        `/appearance/layouts/${layout.layoutViewType}/${layout.layoutID}-${slugify(layout.name)}`,
    AppearanceRoutePageLoader,
);

export const LayoutOverviewRedirectRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/LayoutOverviewRedirectPage"),
    `/appearance/layouts/:layoutID(\\w+)(-[^/]+)?`,
    (layout: LayoutFragment) => `/appearance/layouts/${layout.layoutID}-${slugify(layout.name)}`,
    AppearanceRoutePageLoader,
);

export const AppearanceLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/AppearanceLayoutsPage"),
    "/appearance/layouts",
    () => "/appearance/layouts",
    AppearanceRoutePageLoader,
);

export function getAppearanceRoutes() {
    return [
        AppearanceRoute.route,
        ManageIconsRoute.route,
        LegacyLayoutsRoute.route,
        BrandingPageRoute.route,
        LayoutEditorRoute.route,
        LayoutOverviewRoute.route,
        LayoutOverviewRedirectRoute.route,
    ];
}
