/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { SwaggerUIBundle } from "swagger-ui-dist";
import { formatUrl, getMeta } from "@library/utility/appUtils";
import "swagger-ui-dist/swagger-ui.css";
import "../scss/swagger-ui.scss";

export function mountSwagger() {
    SwaggerUIBundle({
        deepLinking: true,
        dom_id: "#swagger-ui",
        // layout: "DashboardLayout",
        plugins: [SwaggerUIBundle.plugins.DownloadUrl],
        presets: [SwaggerUIBundle.presets.apis],
        requestInterceptor: (request: Request) => {
            request.headers["x-transient-key"] = getMeta("TransientKey");
            return request;
        },
        url: formatUrl("/api/v2/open-api/v3" + window.location.search),
        validatorUrl: null,
    });
}
