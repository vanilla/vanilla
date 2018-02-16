import Embed from "quill/blots/embed";
import { setData, getData } from "@core/dom-utility";

export default class EmojiBlot extends Embed {
    static create(data) {
        const node = super.create();
        node.classList.add("emoji");
        node.innerHTML = data.emojiChar;
        setData(node, data);
        return node;
    }

    static value(node) {
        return getData(node, "data");
    }
}

EmojiBlot.className = 'emoji';
EmojiBlot.blotName = 'emoji';
EmojiBlot.tagName = 'span';
