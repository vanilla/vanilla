/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ClipboardBase from "quill/modules/clipboard";
import Delta from "quill-delta";
import Quill, { DeltaStatic } from "quill/core";
import { rangeContainsBlot } from "@rich-editor/quill/utility";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import ExternalEmbedBlot, { IEmbedValue } from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";

export const EDITOR_SCROLL_CONTAINER_CLASS = "js-richEditorScrollContainer";

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
        this.addMatcher("img", this.imageMatcher);

        // Skip screen reader only content.
        this.addMatcher(".sr-only", () => new Delta());
    }

    /**
     * @override
     * Override the paste handler to
     * - Prevent jumping on paste.
     * - Ensure current selection is deleted before a paste.
     */
    public onPaste(e: Event) {
        if (e.defaultPrevented || !(this.quill as any).isEnabled()) {
            return;
        }
        const range = this.quill.getSelection();
        const container = this.quill.root.closest(`.${EDITOR_SCROLL_CONTAINER_CLASS}`);

        // Get our scroll positions
        const scrollTop = document.documentElement!.scrollTop || document.body.scrollTop;
        const containerTop = container ? container.scrollTop : 0;
        this.container.focus();
        this.quill.selection.update(Quill.sources.SILENT);

        // Delete text if any is currently selected.
        if (range.length) {
            this.quill.deleteText(range.index, range.length, Quill.sources.SILENT);
        }

        // Settimeout so that the paste goes into `this.container`.
        setImmediate(() => {
            // Insert the pasted content.
            const delta = new Delta().retain(range.index).concat(this.convert());
            this.quill.updateContents(delta, Quill.sources.USER);

            // Fix our selection & scroll position.
            this.quill.setSelection(delta.length(), 0, Quill.sources.SILENT);
            document.documentElement!.scrollTop = document.body.scrollTop = scrollTop;
            if (container) {
                container.scrollTop = containerTop;
            }
            this.quill.focus();
        });
    }

    /**
     * A matcher for img tags. Converts `<img />` into an external embed (type image).
     */
    public imageMatcher = (node: HTMLImageElement, delta: DeltaStatic) => {
        const src = node.getAttribute("src");
        const alt = node.getAttribute("alt") || "";
        if (src) {
            const imageData: IEmbedValue = {
                loaderData: {
                    type: "image",
                },
                data: {
                    embedType: "image",
                    url: src,
                    name: alt,
                },
            };
            return new Delta().insert({
                [ExternalEmbedBlot.blotName]: imageData,
            });
        }
        return delta;
    };

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
        const range = this.quill.getLastGoodSelection();
        return rangeContainsBlot(this.quill, CodeBlockBlot, range) || rangeContainsBlot(this.quill, CodeBlot, range);
    }
}
