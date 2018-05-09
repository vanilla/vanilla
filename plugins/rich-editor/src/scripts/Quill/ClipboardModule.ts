/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import BaseClipboard from "quill/modules/clipboard";

export default class ClipboardModule extends BaseClipboard {
    public convert(html: string) {
        console.log("HTML", html);
        console.log("inner", this.container.innerHTML);
        // if (typeof html === "string") {
        //     this.container.innerHTML = html.replace("<p>", "break"); // Remove spaces between tags
        //     return super.convert(html);
        // }
        this.container.childNodes.for;

        return super.convert(html);
    }
}
