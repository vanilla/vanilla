/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import RouteHandler from "@library/routing/RouteHandler";
import PageLoader from "@library/routing/PageLoader";

export const LeavingPageRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/search" */ "@library/leavingPage/LeavingPage"),
    "/home/leaving",
    () => "/home/leaving",
    PageLoader,
);
