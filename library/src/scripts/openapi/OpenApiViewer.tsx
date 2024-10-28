/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { OpenApiLoader } from "@library/openapi/OpenApiViewer.loader";
import { createLoadableComponent } from "@vanilla/react-utils";

export const OpenApiViewer = createLoadableComponent({
    fallback: OpenApiLoader,
    loadFunction: () => import("@library/openapi/OpenApiViewer.loadable").then((m) => m.OpenApiViewerImpl),
});
