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

export default class Formatter {
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
    public paragraph = () => {};
    public h2 = () => {};
    public h3 = () => {};
    public codeBlock = () => {};
    public blockquote = () => {};
    public spoiler = () => {};

    private handleBooleanFormat(formatKey) {
        const isEnabled = this.formats[formatKey] === true;
        this.quill.format(formatKey, !isEnabled, Quill.sources.USER);
    }
}
