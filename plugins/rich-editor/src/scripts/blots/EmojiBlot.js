import Quill from "quill/quill";
let Embed = Quill.import('blots/embed');

export default class EmojiBlot extends Embed {
    static create(emojiData) {
        let node = super.create();
        node.dataset.emoji = emojiData.emojiChar;
        node.classList.add("emoji");
        node.innerHTML = emojiData.emojiChar;
        return node;
    }

    static value(node) {
        return {
            'emojiChar': node.dataset.emoji
        }
    }
}

EmojiBlot.className = 'emoji';
EmojiBlot.blotName = 'emoji';
EmojiBlot.tagName = 'span';
