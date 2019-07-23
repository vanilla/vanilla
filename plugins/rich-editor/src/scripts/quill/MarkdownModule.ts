/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill, { RangeStatic, Blot } from "quill/core";
import HeaderBlot from "@rich-editor/quill/blots/blocks/HeaderBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import Delta from "quill-delta";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";

enum TriggerKey {
    ENTER = "Enter",
    SPACE = " ",
}

interface IMarkdownMatch {
    name: string;
    pattern: RegExp;
    preventsDefault?: boolean;
    triggerKey: TriggerKey;
    handler: (text: string, selection: RangeStatic, pattern: RegExp, lineStart: number) => void;
}

/**
 * Module for handling standard markdown macros.
 *
 * Triggered by the enter key
 * ```           -> Code Block
 *
 * Triggered by a space
 * ###           -> Headings (2-5)
 * >             -> Quote
 * !>            -> Spoiler
 * _text_        -> Italic
 * __text__      -> Bold
 * ___text___    -> Bold & Italic
 * *text*        -> Italic
 * **text**      -> Bold
 * ***text***    -> Bold & Italic
 * ~~text~~      -> Strike
 * `text`        -> Code
 */
export default class MarkdownModule {
    /** HTML tags to ignore keyboard shortcuts inside of. */
    private ignoreTags = ["PRE"];

    constructor(private quill: Quill) {}

    /**
     * Register the event handler for the markdown keyboard shortcuts.
     */
    public registerHandler() {
        // Handler that looks for insert deltas that match specific characters
        this.quill.root.addEventListener("keydown", this.keyDownHandler);
    }

    /**
     * Check if the current quill line is valid for enabling keyboard shortcuts.
     *
     * @param line - The line blot to check.
     */
    private isValidLine(line: Blot): boolean {
        if (!(line.domNode instanceof HTMLElement)) {
            return false;
        }

        const { textContent, tagName } = line.domNode;
        return typeof textContent !== "undefined" && !!textContent && !this.ignoreTags.includes(tagName);
    }

    /**
     * Handle a keydown event and trigger markdown actions.
     */
    private keyDownHandler = (event: KeyboardEvent) => {
        if (!Object.values(TriggerKey).includes(event.key)) {
            return;
        }
        const selection = this.quill.getSelection();
        if (!selection) {
            return;
        }
        const [line, offset] = this.quill.getLine(selection.index);
        const text = line.domNode.textContent;
        const lineStart = selection.index - offset;
        if (!this.isValidLine(line)) {
            return;
        }

        // Iterate through our matchers and execute the first one.
        for (const match of this.matchers) {
            if (match.triggerKey !== event.key) {
                continue;
            }

            const matchedText = text.match(match.pattern);
            if (!matchedText) {
                continue;
            }

            if (match.preventsDefault) {
                event.preventDefault();
                event.stopPropagation();
            }

            // Cutoff the history before an after so this is it's own action to undo.
            this.quill.history.cutoff();
            match.handler(text, selection, match.pattern, lineStart);
            this.quill.history.cutoff();
            break;
        }
    };

    private matchers: IMarkdownMatch[] = [
        {
            name: "header",
            triggerKey: TriggerKey.SPACE,
            preventsDefault: true,
            pattern: /^(#){2,5}$/g,
            handler: (text, selection, pattern) => {
                const match = pattern.exec(text);
                if (!match) {
                    return;
                }
                const hashesLength = match[0].length;

                const delta = new Delta()
                    .retain(selection.index - hashesLength)
                    .delete(hashesLength)
                    .retain(1, { [HeaderBlot.blotName]: hashesLength });
                this.quill.updateContents(delta, Quill.sources.USER);
            },
        },
        {
            name: "blockquote",
            triggerKey: TriggerKey.SPACE,
            pattern: /^(>)/g,
            handler: (text, selection) => {
                const offset = text.length;
                const delta = new Delta()
                    .retain(selection.index - offset)
                    .delete(offset)
                    .retain(1, { [BlockquoteLineBlot.blotName]: true });
                this.quill.updateContents(delta, Quill.sources.USER);
            },
        },
        {
            name: "spoiler",
            triggerKey: TriggerKey.SPACE,
            pattern: /^!>/g,
            handler: (text, selection) => {
                const offset = text.length;
                const delta = new Delta()
                    .retain(selection.index - offset)
                    .delete(offset)
                    .retain(1, { [SpoilerLineBlot.blotName]: true });
                this.quill.updateContents(delta, Quill.sources.USER);
            },
        },
        {
            name: "code-block",
            triggerKey: TriggerKey.ENTER,
            preventsDefault: true,
            pattern: /^`{3}/g,
            handler: (text, selection) => {
                const delta = new Delta()
                    .retain(selection.index - 3)
                    .delete(4) // 1 more for the enter.
                    .insert("\n", { "code-block": true });
                this.quill.updateContents(delta, Quill.sources.USER);
                this.quill.setSelection(selection.index - 3, 0, Quill.sources.USER);
            },
        },
        {
            name: "bolditalic",
            triggerKey: TriggerKey.SPACE,
            pattern: /(?:\*|_){3}(.+?)(?:\*|_){3}/g,
            handler: (text, selection, pattern, lineStart) => {
                const match = pattern.exec(text);
                if (!match) {
                    return;
                }
                const annotatedText = match[0];
                const matchedText = match[1];
                const startIndex = lineStart + match.index;

                if (text.match(/^([*_ \n]+)$/g)) {
                    return;
                }

                this.quill.deleteText(startIndex, annotatedText.length);
                this.quill.insertText(startIndex, matchedText, { bold: true, italic: true });
                this.quill.format("bold", false);
            },
        },
        {
            name: "bold",
            triggerKey: TriggerKey.SPACE,
            pattern: /(?:\*|_){2}(.+?)(?:\*|_){2}/g,
            handler: (text, selection, pattern, lineStart) => {
                const match = pattern.exec(text);
                if (!match) {
                    return;
                }
                const annotatedText = match[0];
                const matchedText = match[1];
                const startIndex = lineStart + match.index;

                if (text.match(/^([*_ \n]+)$/g)) {
                    return;
                }

                this.quill.deleteText(startIndex, annotatedText.length);
                this.quill.insertText(startIndex, matchedText, { bold: true });
                this.quill.format("bold", false);
            },
        },
        {
            name: "italic",
            triggerKey: TriggerKey.SPACE,
            pattern: /(?:\*|_){1}(.+?)(?:\*|_){1}/g,
            handler: (text, selection, pattern, lineStart) => {
                const match = pattern.exec(text);
                if (!match) {
                    return;
                }
                const annotatedText = match[0];
                const matchedText = match[1];
                const startIndex = lineStart + match.index;

                if (text.match(/^([*_ \n]+)$/g)) {
                    return;
                }

                this.quill.deleteText(startIndex, annotatedText.length);
                this.quill.insertText(startIndex, matchedText, { italic: true });
                this.quill.format("italic", false);
            },
        },
        {
            name: "strikethrough",
            triggerKey: TriggerKey.SPACE,
            pattern: /(?:~~)(.+?)(?:~~)/g,
            handler: (text, selection, pattern, lineStart) => {
                const match = pattern.exec(text);
                if (!match) {
                    return;
                }
                const annotatedText = match[0];
                const matchedText = match[1];
                const startIndex = lineStart + match.index;

                if (text.match(/^([*_ \n]+)$/g)) {
                    return;
                }

                this.quill.deleteText(startIndex, annotatedText.length);
                this.quill.insertText(startIndex, matchedText, { strike: true });
                this.quill.format("strike", false);
            },
        },
        {
            name: "code",
            triggerKey: TriggerKey.SPACE,
            pattern: /(?:`)(.+?)(?:`)/g,
            handler: (text, selection, pattern, lineStart) => {
                const match = pattern.exec(text);
                if (!match) {
                    return;
                }

                if (this.quill.getFormat(selection)[CodeBlockBlot.blotName]) {
                    return;
                }

                const annotatedText = match[0];
                const matchedText = match[1];
                const startIndex = lineStart + match.index;

                if (text.match(/^([*_ \n]+)$/g)) {
                    return;
                }

                this.quill.deleteText(startIndex, annotatedText.length);
                this.quill.insertText(startIndex, matchedText, { [CodeBlot.blotName]: true });
                this.quill.format(CodeBlot.blotName, false);
                this.quill.insertText(this.quill.getSelection().index, " ");
            },
        },
    ];
}
