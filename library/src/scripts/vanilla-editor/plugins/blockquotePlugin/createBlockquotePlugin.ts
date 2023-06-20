/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { onKeyDownBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/onKeyDownBlockquote";
import { withBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/withBlockquote";
import { createPluginFactory, ELEMENT_BLOCKQUOTE } from "@udecode/plate-headless";

export const ELEMENT_BLOCKQUOTE_ITEM = "blockquote-line";

export const createBlockquotePlugin = createPluginFactory<{}>({
    key: ELEMENT_BLOCKQUOTE,
    isElement: true,
    deserializeHtml: { rules: [{ validNodeName: "BLOCKQUOTE" }] },
    withOverrides: withBlockquote,
    options: {
        hotkey: "mod+shift+.",
    },
    handlers: {
        onKeyDown: onKeyDownBlockquote,
    },
    plugins: [
        {
            key: ELEMENT_BLOCKQUOTE_ITEM,
            isElement: true,
            deserializeHtml: {
                rules: [
                    {
                        validNodeName: "P",
                        validClassName: "blockquote-content",
                    },
                ],
            },
        },
    ],
});
