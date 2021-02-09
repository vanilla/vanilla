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
import { supportsFrames } from "@vanilla/library/src/scripts/embeddedContent/IFrameEmbed";
import { forceInt } from "@vanilla/utils";

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
            matches.forEach((match) => {
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

        // If frames are supported add their matcher.
        if (supportsFrames()) {
            this.addMatcher("iframe", this.iframeMatcher);
        }
    }

    /**
     * A matcher for img tags. Converts `<img />` into an external embed (type image).
     */
    public iframeMatcher = (node: HTMLIFrameElement, delta: DeltaStatic) => {
        const src = node.getAttribute("src");
        let height = forceInt(node.getAttribute("height"), 900);
        let width = forceInt(node.getAttribute("width"), 1600);
        if (src) {
            const iframeData: IEmbedValue = {
                loaderData: {
                    type: "link",
                },
                data: {
                    embedType: "iframe",
                    url: src,
                    height,
                    width,
                },
            };
            return new Delta().insert({
                [ExternalEmbedBlot.blotName]: iframeData,
            });
        }
        return delta;
    };

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
