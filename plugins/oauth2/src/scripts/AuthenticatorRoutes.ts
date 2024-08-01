/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";

export const AuthenticatorIndexRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/webhooks" */ "@oauth2/pages/AuthenticatorIndexPage"),
    "/oauth2-settings",
    () => "/oauth2-settings",
);

export const AuthenticatorAddEditRoute = new RouteHandler(
    () => import("@oauth2/pages/AuthenticatorAddEdit"),
    ["/oauth2-settings/add", "/oauth2-settings/:authenticatorID/edit"],
    (params: { authenticatorID?: number }) =>
        params.authenticatorID != null ? `/oauth2-settings/${params.authenticatorID}/edit` : "/oauth2-settings/add",
);

export const allAuthenticatorRoutes = [AuthenticatorIndexRoute.route, AuthenticatorAddEditRoute.route];
