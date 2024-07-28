/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { onReady, getMeta, formatUrl, siteUrl } from "@library/utility/appUtils";
import { useSwaggerUI } from "@library/features/swagger/useSwaggerUI";
import { mountReact } from "@vanilla/react-utils";
import { ScrollOffsetProvider, useScrollOffset } from "@library/layout/ScrollOffsetContext";

onReady(async () => {
    const mountPoint = document.getElementById("swagger-ui");
    if (mountPoint) {
        mountReact(
            <ScrollOffsetProvider>
                <SwaggerUI />
            </ScrollOffsetProvider>,
            mountPoint,
        );
    }
});

function SwaggerUI() {
    const scrollOffset = useScrollOffset();
    useEffect(() => {
        scrollOffset.setScrollOffset(104);
    }, []);

    const { swaggerRef } = useSwaggerUI({
        requestInterceptor: (request: Request) => {
            request.headers["x-requested-with"] = getMeta("vanilla");
            return request;
        },
        url: siteUrl("/api/v2/open-api/v3" + window.location.search),
        tryIt: true,
    });

    return <div ref={swaggerRef}></div>;
}
