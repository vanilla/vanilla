/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { withSpoiler } from "@library/vanilla-editor/plugins/spoilerPlugin/withSpoiler";
import { createPluginFactory } from "@udecode/plate-headless";

export const ELEMENT_SPOILER = "spoiler";
export const ELEMENT_SPOILER_ITEM = "spoiler-item";
export const ELEMENT_SPOILER_CONTENT = "spoiler-content";

export const createSpoilerPlugin = createPluginFactory({
    key: ELEMENT_SPOILER,
    isElement: true,
    withOverrides: withSpoiler,
    deserializeHtml: {
        rules: [
            {
                validNodeName: "DIV",
                validClassName: "spoiler",
            },
        ],
    },
    plugins: [
        {
            key: ELEMENT_SPOILER_CONTENT,
            isElement: true,
            deserializeHtml: {
                rules: [
                    {
                        validNodeName: "DIV",
                        validClassName: "spoiler-content",
                    },
                ],
            },
        },
        {
            key: ELEMENT_SPOILER_ITEM,
            isElement: true,
            deserializeHtml: {
                rules: [
                    {
                        validNodeName: "P",
                        validClassName: "spoiler-line",
                    },
                ],
            },
        },
    ],
});
