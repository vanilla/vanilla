/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import RouteHandler from "@library/routing/RouteHandler";
import PageLoader from "@library/routing/PageLoader";
import { ILayout } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { slugify } from "@vanilla/utils";
import {
    getThemeRoutes,
    ThemeEditorRoute,
    ThemePreviewRoute,
    ThemeRevisionsRoute,
} from "@themingapi/routes/themeEditorRoutes";
import { RecordID } from "@vanilla/utils";

export const AppearanceRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/AppearancePage"),
    "/appearance",
    () => "/appearance",
    PageLoader,
);

export const AppearanceLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/AppearanceLayoutsPage"),
    "/appearance/layouts",
    () => "/appearance/layouts",
    PageLoader,
);

export const HomepageLegacyLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/HomepageLegacyLayoutsPage"),
    "/appearance/layouts/homepage/legacy",
    () => "/appearance/layouts/homepage/legacy",
    PageLoader,
);

export const DiscussionsLegacyLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/DiscussionsLegacyLayoutsPage"),
    "/appearance/layouts/discussions/legacy",
    () => "/appearance/layouts/discussions/legacy",
    PageLoader,
);

export const CategoriesLegacyLayoutsRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/CategoriesLegacyLayoutsPage"),
    "/appearance/layouts/categories/legacy",
    () => "/appearance/layouts/categories/legacy",
    PageLoader,
);

export const LayoutEditorRoute = new RouteHandler(
    () => import("@dashboard/layout/pages/LayoutPlaygroundPage"),
    "/appearance/layouts/playground",
    () => "/appearance/layouts/playground",
);

export const BrandingPageRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/BrandingAndSEOPage"),
    "/appearance/branding",
    () => "/appearance/branding",
    PageLoader,
);

export const LayoutOverviewRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/LayoutOverviewPage"),
    `/appearance/layouts/:layoutID(\\w+)(-[^/]+)?`,
    (layout: ILayout) => `/appearance/layouts/${layout.layoutID}-${slugify(layout.name)}`,
    PageLoader,
);

export const StyleGuidesListRoute = new RouteHandler(
    () => import("@dashboard/appearance/pages/StyleGuidesListPage"),
    "/appearance/style-guides",
    (data?: { themeID: RecordID }) => `/appearance/style-guides`,
);

export function getAppearanceRoutes() {
    return [
        AppearanceRoute.route,
        AppearanceLayoutsRoute.route,
        HomepageLegacyLayoutsRoute.route,
        DiscussionsLegacyLayoutsRoute.route,
        CategoriesLegacyLayoutsRoute.route,
        LayoutEditorRoute.route,
        BrandingPageRoute.route,
        LayoutOverviewRoute.route,
        StyleGuidesListRoute.route,
        ThemePreviewRoute.route,
        ThemeEditorRoute.route,
        ThemeRevisionsRoute.route,
    ];
}
