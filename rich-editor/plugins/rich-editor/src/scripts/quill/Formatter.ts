/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "./index";
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

    public bold = (range: RangeStatic) => {
        this.handleBooleanFormat(range, BoldBlot.blotName);
    };

    public italic = (range: RangeStatic) => {
        this.handleBooleanFormat(range, ItalicBlot.blotName);
    };

    public strike = (range: RangeStatic) => {
        this.handleBooleanFormat(range, StrikeBlot.blotName);
    };
    public codeInline = (range: RangeStatic) => {
        this.handleBooleanFormat(range, CodeBlot.blotName);
    };
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
        const line = this.quill.getLine(range.index)[0] as Blot;
        const index = line.offset(this.quill.scroll);
        const length = index + line.length();

        // Code cannot have any inline formattings inside of it.
        for (const inlineFormatName of Formatter.INLINE_FORMAT_NAMES) {
            this.quill.formatText(range.index, range.length, inlineFormatName, false, Quill.sources.API);
        }
        this.quill.formatLine(range.index, range.length, CodeBlockBlot.blotName, true, Quill.sources.USER);
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
}
