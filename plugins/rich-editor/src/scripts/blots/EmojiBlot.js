import Embed from "quill/blots/embed";

export default class EmojiBlot extends Embed {
    static create(emojiData) {
        const node = super.create(emojiData);
        node.classList.add("emoji");
        node.innerHTML = emojiData.emojiChar;
        node.dataset.char = emojiData.emojiChar;
        return node;
    }

    static value(node) {
        return node.dataset.emojiChar;
    }
}

EmojiBlot.className = 'emoji';
EmojiBlot.blotName = 'emoji';
EmojiBlot.tagName = 'span';
