import Quill from "quill";
const Inline = Quill.import("blots/inline");

export default class EmojiBlot extends Inline {

    static blotName = "emoji";
    static tagName = "span";

    static create(classes) {
        let node = super.create();

        console.log("node: ", node);

        classes.split(" ").forEach(iconClass => {
            node.classList.add(iconClass);
        });
        return node;
    }

    static formats(node) {
        let format = {};
        if (node.hasAttribute("class")) {
            format.class = node.getAttribute("class");
        }
        return format;
    }

    static value(node) {
        


        return node.getAttribute("class");
    }

    format(name, value) {
        if (name === "class") {
            if (value) {
                this.domNode.setAttribute(name, value);
            } else {
                this.domNode.removeAttribute(name, value);
            }
        } else {
            super.format(name, value);
        }
    }
}

// EmojiBlot.blotName = "emoji";
// EmojiBlot.tagName = "span";

