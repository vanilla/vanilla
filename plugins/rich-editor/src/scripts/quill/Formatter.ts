/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "./index";
import getStore from "@dashboard/state/getStore";
import { IStoreState } from "@rich-editor/@types/store";
import { getIDForQuill } from "@rich-editor/quill/utility";

export default class Formatter {
    private store = getStore<IStoreState>();
    constructor(private quill: Quill) {}

    public get formats() {
        const id = getIDForQuill(this.quill);
        const selection = this.store.getState().editor.instances[id].lastGoodSelection;
        return selection ? this.quill.getFormat(selection) : {};
    }

    public bold = () => {
        this.handleBooleanFormat("bold");
    };

    public italic = () => {
        this.handleBooleanFormat("italic");
    };

    public strike = () => {
        this.handleBooleanFormat("strike");
    };
    public codeInline = () => {
        this.handleBooleanFormat("code-inline");
    };
    public link = (linkValue?: string) => {
        const isEnabled = typeof this.formats.link === "string";
        if (isEnabled) {
            this.quill.format("link", false, Quill.sources.USER);
        } else {
            this.quill.format("link", linkValue, Quill.sources.USER);
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
