/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Embed from "quill/blots/embed";
import { setData, getData } from "@vanilla/dom-utils";

export default class EmojiBlot extends Embed {
    public static className = "safeEmoji";
    public static blotName = "emoji";
    public static tagName = "span";

    public static create(data) {
        const node = super.create(data) as HTMLElement;
        node.innerText = data.emojiChar;
        node.classList.add("nativeEmoji");
        setData(node, "data", data);
        return node;
    }

    public static value(node) {
        return getData(node, "data");
    }
}
