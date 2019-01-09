/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
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
     * Override the paste event to not jump around on paste in a cross-browser manor.
     *
     * Override https://github.com/quilljs/quill/blob/master/modules/clipboard.js#L108-L123
     * Because of https://github.com/quilljs/quill/issues/1374
     *
     * Hopefully this will be fixed in Quill 2.0
     */
    public onPaste(e: Event) {
        if (e.defaultPrevented || !(this.quill as any).isEnabled()) {
            return;
        }
        const range = this.quill.getSelection();
        let delta = new Delta().retain(range.index);
        const container = this.options.scrollingContainer;

        // THIS IS WHAT IS DIFFERENT
        const scrollTop = document.documentElement!.scrollTop || document.body.scrollTop;
        const containerTop = container ? container.scrollTop : 0;
        this.container.focus();
        (this.quill as any).selection.update(Quill.sources.SILENT);
        setImmediate(() => {
            delta = delta.concat((this as any).convert()).delete(range.length);
            this.quill.updateContents(delta, Quill.sources.USER);
            // range.length contributes to delta.length()
            this.quill.setSelection((delta.length() - range.length) as any, Quill.sources.SILENT);

            // THIS IS WHAT IS DIFFERENT
            document.documentElement!.scrollTop = document.body.scrollTop = scrollTop;
            if (container) {
                container.scrollTop = containerTop;
            }
            this.quill.focus();
        });
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
