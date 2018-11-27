/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ClipboardBase from "quill/modules/clipboard";
import Delta from "quill-delta";
import Quill, { DeltaStatic } from "quill/core";
import { rangeContainsBlot, getIDForQuill } from "@rich-editor/quill/utility";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import getStore from "@library/state/getStore";
import { IStoreState } from "@rich-editor/@types/store";

export default class ClipboardModule extends ClipboardBase {
    /**
     * Split a string into multiple operations, with each link being turned into a full proper link.
     *
     * @param inputText - The text to split up.
     *
     * @returns An array of operations or a null if there were no links.
     */
    public static splitLinkOperationsOutOfText(inputText: string): any[] | null {
        const urlRegex = /https?:\/\/[^\s]+/g;

        const matches = inputText.match(urlRegex);
        if (matches && matches.length > 0) {
            const ops: any[] = [];
            matches.forEach(match => {
                const split = (inputText as string).split(match);
                const beforeLink = split.shift();
                // We don't want to insert empty ops.
                if (beforeLink !== "") {
                    ops.push({ insert: beforeLink });
                }
                ops.push({ insert: match, attributes: { link: match } });
                inputText = split.join(match);
            });
            // We don't want to insert empty ops.
            if (inputText !== "") {
                ops.push({ insert: inputText });
            }
            return ops;
        } else {
            return null;
        }
    }

    constructor(quill, options) {
        super(quill, options);
        this.addMatcher(Node.TEXT_NODE, this.linkMatcher);
    }

    /**
     * A matcher to turn a pasted links into real links.
     */
    public linkMatcher = (node: Node, delta: DeltaStatic) => {
        const { textContent } = node;
        if (node.nodeType === Node.TEXT_NODE && textContent != null) {
            if (!this.inCodeFormat) {
                const splitOps = ClipboardModule.splitLinkOperationsOutOfText(textContent);
                if (splitOps) {
                    delta.ops = splitOps;
                }
            }
        }

        return delta;
    };

    /**
     * Determine if we are in a code formatted item or not.
     */
    private get inCodeFormat() {
        const instance = getStore<IStoreState>().getState().editor.instances[getIDForQuill(this.quill)];
        if (!instance || !instance.lastGoodSelection) {
            return false;
        }
        return (
            rangeContainsBlot(this.quill, CodeBlockBlot, instance.lastGoodSelection) ||
            rangeContainsBlot(this.quill, CodeBlot, instance.lastGoodSelection)
        );
    }
}
