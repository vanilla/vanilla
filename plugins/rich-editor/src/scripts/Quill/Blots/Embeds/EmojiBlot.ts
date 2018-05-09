/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Embed from "quill/blots/embed";
import { setData, getData, convertToSafeEmojiCharacters, isEmojiSupported } from "@core/dom";

export default class EmojiBlot extends Embed {
    public static className = "safeEmoji";
    public static blotName = "emoji";
    public static tagName = "span";

    public static create(data) {
        const node = super.create(data) as HTMLElement;
        if (isEmojiSupported()) {
            node.innerHTML = data.emojiChar;
            node.classList.add("nativeEmoji");
        } else {
            node.innerHTML = convertToSafeEmojiCharacters(data.emojiChar);
        }
        setData(node, "data", data);
        return node;
    }

    public static value(node) {
        return getData(node, "data");
    }
}
