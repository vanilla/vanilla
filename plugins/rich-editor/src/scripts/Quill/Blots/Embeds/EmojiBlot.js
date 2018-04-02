/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Embed from "quill/blots/embed";
import { setData, getData } from "@core/dom-utility";
import { parseEmoji, isEmojiSupported } from "@core/emoji-utility";

export default class EmojiBlot extends Embed {

    static className = 'safeEmoji';
    static blotName = 'emoji';
    static tagName = 'span';

    static create(data) {
        const node = super.create();
        if (isEmojiSupported()) {
            node.innerHTML = data.emojiChar;
            node.classList.add("nativeEmoji");
        } else {
            node.innerHTML = parseEmoji(data.emojiChar);
        }
        setData(node, "data", data);
        return node;
    }

    static value(node) {
        return getData(node, "data");
    }
}
