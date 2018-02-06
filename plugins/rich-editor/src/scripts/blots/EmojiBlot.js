import Embed from "quill/blots/embed";

export default class EmojiBlot extends Embed {
    static create(emojiData) {
        const node = super.create();
        node.classList.add("emoji");
        node.innerHTML = emojiData.emojiChar;
        return node;
    }
    static value(node) {
        return {
            emojiChar: node.dataset.emoji,
        }
    }
}

EmojiBlot.className = 'emoji';
EmojiBlot.blotName = 'emoji';
EmojiBlot.tagName = 'span';
