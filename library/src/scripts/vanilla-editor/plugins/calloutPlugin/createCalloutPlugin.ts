/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { createPluginFactory } from "@udecode/plate-common";
import { onKeyDownCallout } from "@library/vanilla-editor/plugins/calloutPlugin/onKeyDownCallout";
import { withCallout } from "@library/vanilla-editor/plugins/calloutPlugin/withCallout";

export const ELEMENT_CALLOUT = "callout";
export const ELEMENT_CALLOUT_ITEM = "callout-item";
export type CalloutAppearance = "info" | "warning" | "neutral" | "alert";

export const createCalloutPlugin = createPluginFactory<{}>({
    key: ELEMENT_CALLOUT,
    isElement: true,
    withOverrides: withCallout,
    handlers: {
        onKeyDown: onKeyDownCallout,
    },
    deserializeHtml: {
        rules: [
            {
                validNodeName: "DIV",
                validClassName: "callout",
            },
        ],
    },
    plugins: [
        {
            key: ELEMENT_CALLOUT_ITEM,
            isElement: true,
            deserializeHtml: {
                rules: [
                    {
                        validNodeName: "P",
                        validClassName: "callout-line",
                    },
                ],
            },
        },
    ],
});
