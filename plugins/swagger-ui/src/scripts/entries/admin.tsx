/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { onReady, getMeta, formatUrl, siteUrl } from "@library/utility/appUtils";
import { useSwaggerUI } from "@vanilla/library/src/scripts/features/swagger/useSwaggerUI";
import { mountReact } from "@vanilla/react-utils";

onReady(async () => {
    const mountPoint = document.getElementById("swagger-ui");
    if (mountPoint) {
        mountReact(<SwaggerUI />, mountPoint);
    }
});

function SwaggerUI() {
    const { swaggerRef } = useSwaggerUI({
        requestInterceptor: (request: Request) => {
            request.headers["x-transient-key"] = getMeta("TransientKey");
            return request;
        },
        url: siteUrl("/api/v2/open-api/v3" + window.location.search),
    });

    return <div ref={swaggerRef}></div>;
}
