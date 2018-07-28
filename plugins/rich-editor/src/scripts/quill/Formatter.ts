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

    private get formats() {
        const id = getIDForQuill(this.quill);
        const selection = this.store.getState().editor.instances[id].lastGoodSelection;
        return selection ? this.quill.getFormat(selection) : {};
    }

    public bold = () => {};

    public italic = () => {};

    public strike = () => {};
    public codeInline = () => {};
    public link = (linkValue: string) => {};
    public paragraph = () => {};
    public h2 = () => {};
    public h3 = () => {};
    public codeBlock = () => {};
    public blockquote = () => {};
    public spoiler = () => {};
}
