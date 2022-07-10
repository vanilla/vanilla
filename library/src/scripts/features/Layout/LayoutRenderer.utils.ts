/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IHydratedLayoutWidget } from "@library/features/Layout/LayoutRenderer.types";

/**
 * Utility function to test if a given object is indeed a DynamicComponent configuration
 * by testing that $reactComponent and $reactProps keys exist
 */
export function isHydratedLayoutWidget(node: unknown): node is IHydratedLayoutWidget {
    if (!node) {
        return false;
    }

    if (typeof node !== "object") {
        return false;
    }
    return !!node && node.hasOwnProperty("$reactComponent") && node.hasOwnProperty("$reactProps");
}
