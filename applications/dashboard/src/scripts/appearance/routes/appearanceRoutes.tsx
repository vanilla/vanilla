/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import RouteHandler from "@library/routing/RouteHandler";
import PageLoader from "@library/routing/PageLoader";
import { ILayoutDetails, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { slugify } from "@vanilla/utils";

type LayoutFragment = {
    layoutID: ILayoutDetails["layoutID"];
    name: ILayoutDetails["name"];
    layoutViewType: ILayoutDetails["layoutViewType"];
};

export const AppearanceRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/AppearancePage"),
    "/appearance",
    () => "/appearance",
    PageLoader,
);

export const LegacyLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/LegacyLayoutsPage"),
    "/appearance/layouts/:layoutViewType/legacy",
    (layoutViewType: LayoutViewType) => `/appearance/layouts/${layoutViewType}/legacy`,
    PageLoader,
);

export const BrandingPageRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/BrandingAndSEOPage"),
    "/appearance/branding",
    () => "/appearance/branding",
    PageLoader,
);

export const LayoutEditorRoute = new RouteHandler(
    () => import("@dashboard/layout/pages/LayoutEditorPage"),
    [`/appearance/layouts/:layoutViewType/:layoutID(\\w+)(-[^/]+)?/edit`, "/appearance/layouts/:layoutViewType/add"],
    (layout: Partial<LayoutFragment>) => {
        if (layout.layoutID) {
            let slug = layout.layoutID;
            if (layout.name) {
                slug += "-" + slugify(layout.name);
            }
            return `/appearance/layouts/${layout.layoutViewType}/${slug}/edit`;
        } else {
            return `/appearance/layouts/${layout.layoutViewType}/add`;
        }
    },
    PageLoader,
);

export const LayoutOverviewRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/LayoutOverviewPage"),
    `/appearance/layouts/:layoutViewType/:layoutID(\\w+)(-[^/]+)?`,
    (layout: LayoutFragment) =>
        `/appearance/layouts/${layout.layoutViewType}/${layout.layoutID}-${slugify(layout.name)}`,
    PageLoader,
);

export const LayoutOverviewRedirectRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/LayoutOverviewRedirectPage"),
    `/appearance/layouts/:layoutID(\\w+)(-[^/]+)?`,
    (layout: LayoutFragment) => `/appearance/layouts/${layout.layoutID}-${slugify(layout.name)}`,
    PageLoader,
);

export const AppearanceLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/AppearanceLayoutsPage"),
    "/appearance/layouts",
    () => "/appearance/layouts",
    PageLoader,
);

export function getAppearanceRoutes() {
    return [
        AppearanceRoute.route,
        LegacyLayoutsRoute.route,
        BrandingPageRoute.route,
        LayoutEditorRoute.route,
        LayoutOverviewRoute.route,
        LayoutOverviewRedirectRoute.route,
    ];
}
