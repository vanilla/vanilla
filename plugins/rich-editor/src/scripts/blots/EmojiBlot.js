import Embed from "quill/blots/embed";
import { setData, getData} from "@core/dom-utility";
import { parseEmoji, isEmojiSupported } from "@core/emoji-utility";

export default class EmojiBlot extends Embed {
    static create(data) {
        const node = super.create();
        if (isEmojiSupported()) { // Native support
            node.innerHTML = data.emojiChar;
            node.classList.add("nativeEmoji");
        } else {
            const fallbackEmoji = parseEmoji(data.emojiChar);
            node.innerHTML = fallbackEmoji + " "; // the space is important to make it like a "word"
        }
        setData(node, "data", data);
        return node;
    }

    static value(node) {
        return getData(node, "data");
    }
}

EmojiBlot.className = 'smartEmoji';
EmojiBlot.blotName = 'emoji';
EmojiBlot.tagName = 'span';
