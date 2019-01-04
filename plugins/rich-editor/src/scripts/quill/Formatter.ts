/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill from "quill/core";
import LinkBlot from "quill/formats/link";
import BoldBlot from "quill/formats/bold";
import ItalicBlot from "quill/formats/italic";
import StrikeBlot from "quill/formats/strike";
import { rangeContainsBlot, disableAllBlotsInRange } from "@rich-editor/quill/utility";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import HeadingBlot from "quill/formats/header";
import { Blot, RangeStatic } from "quill/core";
import Embed from "quill/blots/embed";

export default class Formatter {
    public static INLINE_FORMAT_NAMES = [
        BoldBlot.blotName,
        ItalicBlot.blotName,
        StrikeBlot.blotName,
        CodeBlot.blotName,
        LinkBlot.blotName,
    ];

    public static BLOCK_FORMAT_NAMES = [
        CodeBlockBlot.blotName,
        BlockquoteLineBlot.blotName,
        SpoilerLineBlot.blotName,
        HeadingBlot.blotName,
    ];

    constructor(private quill: Quill) {}

    /**
     * Apply the bold format to a range.
     */
    public bold = (range: RangeStatic) => {
        this.handleInlineFormat(range, BoldBlot.blotName);
    };

    /**
     * Apply the italic format to a range.
     */
    public italic = (range: RangeStatic) => {
        this.handleInlineFormat(range, ItalicBlot.blotName);
    };

    /**
     * Apply the strike format to a range.
     */
    public strike = (range: RangeStatic) => {
        this.handleInlineFormat(range, StrikeBlot.blotName);
    };

    /**
     * Apply the codeInline format to a range.
     */
    public codeInline = (range: RangeStatic) => {
        this.handleInlineFormat(range, CodeBlot.blotName);
    };

    /**
     * Apply the link format to a range.
     */
    public link = (range: RangeStatic, linkValue?: string) => {
        const isEnabled = rangeContainsBlot(this.quill, LinkBlot, range);
        if (isEnabled) {
            disableAllBlotsInRange(this.quill, LinkBlot as any, range);
        } else {
            this.quill.formatText(range.index, range.length, LinkBlot.blotName, linkValue, Quill.sources.USER);
        }
    };

    /**
     * Apply the paragraph line format to all lines in the range.
     */
    public paragraph = (range: RangeStatic) => {
        Formatter.BLOCK_FORMAT_NAMES.forEach(name => {
            this.quill.formatLine(range.index, range.length, name, false, Quill.sources.API);
        });
        this.quill.update(Quill.sources.USER);
    };

    /**
     * Apply the h2 line format to all lines in the range.
     */
    public h2 = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, HeadingBlot.blotName, 2, Quill.sources.USER);
    };

    /**
     * Apply the h3 line format to all lines in the range.
     */
    public h3 = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, HeadingBlot.blotName, 3, Quill.sources.USER);
    };

    /**
     * Apply codeBlock line format to all lines in the range. This will strip all other formats.
     * Additionally it will convert inline embeds into text.
     */
    public codeBlock = (range: RangeStatic) => {
        let lines = this.quill.getLines(range.index, range.length) as Blot[];
        if (lines.length === 0) {
            lines = [this.quill.getLine(range.index)[0] as Blot];
        }
        const firstLine = lines[0];
        const lastLine = lines[lines.length - 1];
        const start = firstLine.offset(this.quill.scroll);
        const length = lastLine.offset(this.quill.scroll) + lastLine.length() - start;
        const fullRange = {
            index: start,
            length,
        };
        Formatter.INLINE_FORMAT_NAMES.forEach(name => {
            this.quill.formatText(start, length, name, false, Quill.sources.SILENT);
        });
        const difference = this.replaceInlineEmbeds(fullRange);
        this.quill.formatLine(start, length + difference, CodeBlockBlot.blotName, true, Quill.sources.USER);
    };

    /**
     * Apply the blockquote line format to all lines in the range.
     */
    public blockquote = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, BlockquoteLineBlot.blotName, true, Quill.sources.USER);
    };

    /**
     * Apply the spoiler line format to all lines in the range.
     */
    public spoiler = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, SpoilerLineBlot.blotName, true, Quill.sources.USER);
    };

    /**
     * Apply an inline format with a true/false enable value.
     *
     * @param range - The Range to apply the format to.
     * @param formatKey - The key of the format. This is generally the blotName of a blot.
     */
    private handleInlineFormat(range: RangeStatic, formatKey: string) {
        const formats = this.quill.getFormat(range);
        const isEnabled = formats[formatKey] === true;
        this.quill.formatText(range.index, range.length, formatKey, !isEnabled, Quill.sources.USER);
        this.quill.update(Quill.sources.USER);
    }

    /**
     * Replace all inline embeds within a blot with their plaintext equivalent.
     */
    private replaceInlineEmbeds(range: RangeStatic): number {
        const embeds = this.quill.scroll.descendants(Embed as any, range.index, range.length);
        let lengthDifference = 0;
        embeds.forEach(embed => {
            let text = (embed.domNode as HTMLElement).innerText || "";
            // Strip 0-width whitespace.
            text = text.replace(/[\u200B-\u200D\uFEFF]/g, "");

            lengthDifference += text.length - 1;
            embed.replaceWith("text", text);
            this.quill.update(Quill.sources.USER);
        });
        return lengthDifference;
    }
}
