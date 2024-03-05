/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import RouteHandler from "@library/routing/RouteHandler";
import PageLoader from "@library/routing/PageLoader";
import { ConvertHtmlRouteParams } from "@library/convertHTML/ConvertHTML";

export const ConvertHTMLPageRoute = new RouteHandler(
    () => import(/* webpackChunkName: "utility/convert-html" */ "@library/convertHTML/ConvertHTML"),
    "/utility/convert-html/:format/:recordType/:recordID",
    (params: ConvertHtmlRouteParams) =>
        `/utility/convert-html/${params.format}/${params.recordType}/${params.recordID}`,
    PageLoader,
);
