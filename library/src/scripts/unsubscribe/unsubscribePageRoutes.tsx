/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";
import PageLoader from "@library/routing/PageLoader";

export const UnsubscribePageRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/search" */ "@library/unsubscribe/UnsubscribePage"),
    "/unsubscribe/:token?",
    (token: string) => `/unsubscribe/${token}`,
    PageLoader,
);
