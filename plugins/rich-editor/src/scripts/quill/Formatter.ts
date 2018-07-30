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
import getStore from "@dashboard/state/getStore";
import { IStoreState } from "@rich-editor/@types/store";
import { getIDForQuill, rangeContainsBlot, disableAllBlotsInRange } from "@rich-editor/quill/utility";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import HeadingBlot from "quill/formats/header";
import { Blot } from "quill/core";

export default class Formatter {
    private static INLINE_FORMAT_NAMES = [
        BoldBlot.blotName,
        ItalicBlot.blotName,
        StrikeBlot.blotName,
        CodeBlot.blotName,
        LinkBlot.blotName,
    ];

    private static BLOCK_FORMAT_NAMES = [
        CodeBlockBlot.blotName,
        BlockquoteLineBlot.blotName,
        SpoilerLineBlot.blotName,
        HeadingBlot.blotName,
    ];

    private store = getStore<IStoreState>();
    constructor(private quill: Quill) {}

    private get instanceState() {
        const id = getIDForQuill(this.quill);
        return this.store.getState().editor.instances[id];
    }

    public get formats() {
        const selection = this.instanceState.lastGoodSelection;
        return selection ? this.quill.getFormat(selection) : {};
    }

    public bold = () => {
        this.handleBooleanFormat(BoldBlot.blotName);
    };

    public italic = () => {
        this.handleBooleanFormat(ItalicBlot.blotName);
    };

    public strike = () => {
        this.handleBooleanFormat(StrikeBlot.blotName);
    };
    public codeInline = () => {
        this.handleBooleanFormat(CodeBlot.blotName);
    };
    public link = (linkValue?: string) => {
        const isEnabled = rangeContainsBlot(this.quill, LinkBlot, this.instanceState.lastGoodSelection);
        if (isEnabled) {
            disableAllBlotsInRange(this.quill, LinkBlot, this.instanceState.lastGoodSelection);
        } else {
            this.quill.format(LinkBlot.blotName, linkValue, Quill.sources.USER);
        }
    };
    public paragraph = () => {
        Formatter.BLOCK_FORMAT_NAMES.forEach(name => {
            this.quill.format(name, false, Quill.sources.API);
        });
        this.quill.update(Quill.sources.USER);
    };
    public h2 = () => {
        this.quill.format(HeadingBlot.blotName, 2, Quill.sources.USER);
    };
    public h3 = () => {
        this.quill.format(HeadingBlot.blotName, 3, Quill.sources.USER);
    };
    public codeBlock = () => {
        const line = this.quill.getLine(this.instanceState.lastGoodSelection.index)[0] as Blot;
        const index = line.offset(this.quill.scroll);
        const length = index + line.length();

        // Code cannot have any inline formattings inside of it.
        this.quill.removeFormat(index, length, Quill.sources.API);
        this.quill.format(CodeBlockBlot.blotName, true, Quill.sources.USER);
    };
    public blockquote = () => {
        this.quill.format(BlockquoteLineBlot.blotName, true, Quill.sources.USER);
    };
    public spoiler = () => {
        this.quill.format(SpoilerLineBlot.blotName, true, Quill.sources.USER);
    };

    private handleBooleanFormat(formatKey) {
        const isEnabled = this.formats[formatKey] === true;
        this.quill.format(formatKey, !isEnabled, Quill.sources.USER);
    }
}
