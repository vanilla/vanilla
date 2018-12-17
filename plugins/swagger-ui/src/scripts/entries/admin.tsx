/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady } from "@library/application";

onReady(async () => {
    const mountPoint = document.getElementById("swagger-ui");
    if (mountPoint) {
        const { mountSwagger } = await import("../mountSwagger");
        mountSwagger();
    }
});
