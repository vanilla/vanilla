/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import PageLoaderWithTitleBar from "@library/routing/PageLoaderWithTitleBar";
import RouteHandler from "@library/routing/RouteHandler";

export const DraftsPageRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/drafts" */ "@vanilla/addon-vanilla/drafts/pages/DraftsPage"),
    "/drafts",
    () => "/drafts",
    PageLoaderWithTitleBar,
);
