/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
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
import Parchment from "parchment";
import Embed from "quill/blots/embed";
import ExternalEmbedBlot from "@rich-editor/quill/blots/embeds/ExternalEmbedBlot";
import HistoryModule from "@rich-editor/quill/HistoryModule";

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
    private historyModule: HistoryModule;

    constructor(private quill: Quill) {
        this.historyModule = quill.getModule("history");
    }

    /**
     * Apply the bold format to a range.
     */
    public bold = (range: RangeStatic) => {
        this.handleBooleanFormat(range, BoldBlot.blotName);
    };

    /**
     * Apply the italic format to a range.
     */
    public italic = (range: RangeStatic) => {
        this.handleBooleanFormat(range, ItalicBlot.blotName);
    };

    /**
     * Apply the strike format to a range.
     */
    public strike = (range: RangeStatic) => {
        this.handleBooleanFormat(range, StrikeBlot.blotName);
    };

    /**
     * Apply the codeInline format to a range.
     */
    public codeInline = (range: RangeStatic) => {
        this.handleBooleanFormat(range, CodeBlot.blotName);
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
    public paragraph = (range: RangeStatic) => {
        Formatter.BLOCK_FORMAT_NAMES.forEach(name => {
            this.quill.formatLine(range.index, range.length, name, false, Quill.sources.API);
        });
        this.quill.update(Quill.sources.USER);
    };
    public h2 = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, HeadingBlot.blotName, 2, Quill.sources.USER);
    };
    public h3 = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, HeadingBlot.blotName, 3, Quill.sources.USER);
    };
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
        const difference = this.replaceInlineEmbeds(fullRange);
        Formatter.INLINE_FORMAT_NAMES.forEach(name => {
            this.quill.formatText(start, length, name, false, Quill.sources.API);
        });
        this.quill.formatLine(start, length + difference, CodeBlockBlot.blotName, true, Quill.sources.USER);
    };
    public blockquote = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, BlockquoteLineBlot.blotName, true, Quill.sources.USER);
    };
    public spoiler = (range: RangeStatic) => {
        this.quill.formatLine(range.index, range.length, SpoilerLineBlot.blotName, true, Quill.sources.USER);
    };

    private handleBooleanFormat(range: RangeStatic, formatKey: string) {
        const formats = this.quill.getFormat(range);
        const isEnabled = formats[formatKey] === true;
        this.quill.formatText(range.index, range.length, formatKey, !isEnabled, Quill.sources.USER);
        this.quill.update(Quill.sources.USER);
    }

    private replaceInlineEmbeds(range: RangeStatic): number {
        const embeds = this.quill.scroll.descendants(Embed as any, range.index, range.length);
        let lengthDifference = 0;
        embeds.forEach(embed => {
            const text = (embed.domNode as HTMLElement).innerText || "";
            lengthDifference += text.length - 1;
            embed.replaceWith("text", text);
            this.quill.update(Quill.sources.USER);
        });
        return lengthDifference;
    }
}
