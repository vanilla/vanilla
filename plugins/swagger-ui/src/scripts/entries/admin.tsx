/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady } from "@library/utility/appUtils";

onReady(async () => {
    const mountPoint = document.getElementById("swagger-ui");
    if (mountPoint) {
        const { mountSwagger } = await import(/* webpackChunkName: "mountEditor" */ "../mountSwagger");
        mountSwagger();
    }
});
