/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import RouteHandler from "@library/routing/RouteHandler";
import PageLoader from "@library/routing/PageLoader";
import { ILayout, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { slugify } from "@vanilla/utils";

type LayoutFragment = {
    layoutID: ILayout["layoutID"];
    name: ILayout["name"];
    layoutViewType: ILayout["layoutViewType"];
};

export const AppearanceRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/AppearancePage"),
    "/appearance",
    () => "/appearance",
    PageLoader,
);

export const LayoutPlaygroundPage = new RouteHandler(
    () => import("@dashboard/layout/pages/LayoutPlaygroundPage"),
    "/appearance/layouts/playground",
    () => `/appearance/layouts/playground`,
    PageLoader,
);

export const NewLayoutJsonRoute = new RouteHandler(
    () => import("@dashboard/layout/pages/NewLayoutJsonPage"),
    "/appearance/layouts/:layoutViewType/add/json",
    (layoutViewType: LayoutViewType) => `/appearance/layouts/${layoutViewType}/add/json`,
    PageLoader,
);

export const NewLayoutRoute = new RouteHandler(
    () => import("@dashboard/layout/pages/NewLayoutPage"),
    "/appearance/layouts/:layoutViewType/add",
    (layoutViewType: LayoutViewType) => `/appearance/layouts/${layoutViewType}/add`,
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

export const EditLayoutRoute = new RouteHandler(
    () => import("@dashboard/layout/pages/EditLayoutPage"),
    `/appearance/layouts/:layoutViewType/:layoutID(\\w+)(-[^/]+)?/edit`,
    (layout: LayoutFragment) =>
        `/appearance/layouts/${layout.layoutViewType}/${layout.layoutID}-${slugify(layout.name)}/edit`,
    PageLoader,
);

export const EditLayoutJsonRoute = new RouteHandler(
    () => import("@dashboard/layout/pages/EditLayoutJsonPage"),
    `/appearance/layouts/:layoutViewType/:layoutID(\\w+)(-[^/]+)?/edit/json`,
    (layout: LayoutFragment) =>
        `/appearance/layouts/${layout.layoutViewType}/${layout.layoutID}-${slugify(layout.name)}/edit/json`,
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
        LayoutPlaygroundPage.route,
        NewLayoutJsonRoute.route,
        NewLayoutRoute.route,
        BrandingPageRoute.route,
        EditLayoutRoute.route,
        EditLayoutJsonRoute.route,
        LayoutOverviewRoute.route,
        LayoutOverviewRedirectRoute.route,
    ];
}
