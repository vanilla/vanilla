/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import EmbedBlot from "quill/blots/embed";
import Quill, { RangeStatic, Blot } from "quill/core";
import HeaderBlot from "@rich-editor/quill/blots/blocks/HeaderBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import Delta from "quill-delta";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import Formatter from "@rich-editor/quill/Formatter";
import ContainerBlot from "quill/blots/container";

export enum MarkdownBlockTriggers {
    ENTER = "Enter",
    SPACE = " ",
}

export enum MarkdownMacroType {
    INLINE = "inline",
    BLOCK = "block",
}

export enum MarkdownInlineTriggers {
    ENTER = "Enter",
    SPACE = " ",
    PERIOD = ".",
    COMMA = ",",
    QUESTION = "?",
    QUESTION_INVERT = "¿",
    SLASH = "/",
    ESCLAME = "!",
    ESCLAME_INVERT = "¡",
    QUOTE = '"',
    APOSTOPH = "'",
    STAR = "*",
    COLON = ":",
    SEMI_COLON = ";",
}

// Precompjuted to prevent doing it on every keypress.
const allTriggerKeys = [...Object.values(MarkdownBlockTriggers), ...Object.values(MarkdownInlineTriggers)];

interface IMarkdownMatch {
    name: string;
    pattern: RegExp;
    preventsDefault?: boolean;
    type: MarkdownMacroType;
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
     * @param range - The range to check.
     */
    private canFormatRange(range: RangeStatic): boolean {
        const formats = this.quill.getFormat(range);
        let hasExistingInlineFormat = false;
        Formatter.INLINE_FORMAT_NAMES.forEach(name => {
            if (formats[name]) {
                hasExistingInlineFormat = true;
            }
        });

        return !hasExistingInlineFormat;
    }

    private getFormattableText(): {
        text: string;
        lineStart: number;
    } | null {
        const selection = this.quill.getSelection();
        if (!selection) {
            return null;
        }

        // Only short selections apply.
        if (selection.length > 0) {
            return null;
        }

        const [line, offset] = this.quill.getLine(selection.index);
        if (!line.children) {
            return null;
        }
        let lineStart = line.offset(this.quill.scroll);
        let text = this.quill.getText(lineStart, selection.index - lineStart);

        // Adjust the text so that it's only after the last whitespace character.
        // Because this is a markdown MACRO and not a parser, we only look at stuff after the previous whitespace character.
        const lastIndex = text.lastIndexOf(" ");
        if (lastIndex >= 0) {
            // We can't use the normal index because `getText()` isn't very smart about selecting inline embeds.
            // See original get text implementation https://github.com/quilljs/quill/blob/develop/core/editor.js#L147-L152
            // It needs to be offset by the count of inline embeds which each have a length of 1.
            let inlineEmbedCount = 0;
            const checkBlot = (child: Blot) => {
                if (child instanceof EmbedBlot) {
                    inlineEmbedCount++;
                    return;
                }

                if ((child as ContainerBlot).children) {
                    (child as ContainerBlot).children.forEach(checkBlot);
                }
            };
            line.children.forEachAt(0, selection.index - lineStart, checkBlot);
            lineStart += lastIndex + inlineEmbedCount + 1;
            text = text.substr(lastIndex + 1);
        }

        if (!this.canFormatRange({ index: lineStart, length: text.length })) {
            return null;
        }

        return {
            text,
            lineStart,
        };
    }

    /**
     * Handle a keydown event and trigger markdown actions.
     */
    private keyDownHandler = (event: KeyboardEvent) => {
        if (!allTriggerKeys.includes(event.key)) {
            return;
        }
        const result = this.getFormattableText();
        if (!result) {
            return;
        }
        const { text, lineStart } = result;

        // Iterate through our matchers and execute the first one.
        for (const match of this.matchers) {
            switch (match.type) {
                case MarkdownMacroType.INLINE:
                    if (!Object.values(MarkdownInlineTriggers).includes(event.key)) {
                        continue;
                    }
                    break;
                case MarkdownMacroType.BLOCK:
                    if (!Object.values(MarkdownBlockTriggers).includes(event.key)) {
                        continue;
                    }
                    break;
            }

            const matchedText = text.match(match.pattern);
            if (!matchedText) {
                continue;
            }

            if (match.preventsDefault) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
            }

            // Cutoff the history before an after so this is it's own action to undo.
            this.quill.history.cutoff();
            match.handler(text, this.quill.getSelection(), match.pattern, lineStart);
            this.quill.history.cutoff();
            return false;
        }
    };

    private matchers: IMarkdownMatch[] = [
        {
            name: "header",
            type: MarkdownMacroType.BLOCK,
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
            type: MarkdownMacroType.BLOCK,
            preventsDefault: true,
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
            type: MarkdownMacroType.BLOCK,
            pattern: /^!>$/g,
            preventsDefault: true,
            handler: (text, selection) => {
                const offset = text.length;
                const delta = new Delta()
                    .retain(selection.index - offset)
                    .delete(offset)
                    .retain(1, { [SpoilerLineBlot.blotName]: true })
                    .delete(1);
                this.quill.updateContents(delta, Quill.sources.USER);
            },
        },
        {
            name: "code-block",
            type: MarkdownMacroType.BLOCK,
            preventsDefault: true,
            pattern: /^`{3}$/g,
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
            type: MarkdownMacroType.INLINE,
            pattern: /(?:\*|_){3}(.+?)(?:\*|_){3}$/g,
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
            type: MarkdownMacroType.INLINE,
            pattern: /(?:\*|_){2}(.+?)(?:\*|_){2}$/g,
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
            type: MarkdownMacroType.INLINE,
            pattern: /(?:\*|_){1}(.+?)(?:\*|_){1}$/g,
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
            type: MarkdownMacroType.INLINE,
            pattern: /(?:~~)(.+?)(?:~~)$/g,
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
            type: MarkdownMacroType.INLINE,
            pattern: /(?:`)(.+?)(?:`)$/g,
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
