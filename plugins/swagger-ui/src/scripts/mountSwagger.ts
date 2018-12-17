/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import SwaggerUI from "swagger-ui";
import { formatUrl, getMeta } from "@library/application";
import "../scss/swagger-ui.scss";

export function mountSwagger() {
    SwaggerUI({
        deepLinking: true,
        dom_id: "#swagger-ui",
        // layout: "DashboardLayout",
        plugins: [SwaggerUI.plugins.DownloadUrl],
        presets: [SwaggerUI.presets.apis],
        requestInterceptor: (request: Request) => {
            request.headers["x-transient-key"] = getMeta("TransientKey");
            return request;
        },
        url: formatUrl("/api/v2/open-api/v2"),
        validatorUrl: null,
    });
}
