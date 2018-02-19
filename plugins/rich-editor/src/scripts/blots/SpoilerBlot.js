import Container from "quill/blots/container";
import Block from 'quill/blots/block';
import Parchment from "parchment";

export class SpoilerContentBlot extends Container {

    static blotName = "spoiler-content";
    static className = "spoiler-content";
    static tagName = 'div';
    static scope = Parchment.Scope.BLOCK_BLOT;
    static defaultChild = 'block';
    static allowedChildren = [Block];

    static create() {
        const domNode = super.create();
        domNode.classList.add('spoiler-content');
        return domNode;
    }

    // replaceWith(name, value) {
    //     this.parent.isolate(this.offset(this.parent), this.length());
    //     if (name === this.parent.statics.blotName) {
    //         this.parent.replaceWith(name, value);
    //         return this;
    //     } else {
    //         this.parent.unwrap();
    //         return super.replaceWith(name, value);
    //     }
    // }
    replace(target) {
        if (target.statics.blotName !== this.statics.blotName) {
            let item = Parchment.create(this.statics.defaultChild);
            target.moveChildren(item);
            this.appendChild(item);
        }
        super.replace(target);
    }
}

export default class SpoilerBlot extends Container {

    static blotName = 'spoiler';
    static className = 'spoiler';
    static tagName = 'div';
    static scope = Parchment.Scope.BLOCK_BLOT;
    static defaultChild = 'spoiler-content';
    static allowedChildren = [SpoilerContentBlot];

    static create() {
        const domNode = super.create();
        domNode.classList.add('spoiler');
        return domNode;
    }

    // insertAt(index, value, def) {
    //     console.log("insert!");
    //     console.log(value);
    //     this.children.head.insertAt(index, value, def);
    // }

    // formatAt(index, length, name, value) {
    //     this.children.head.insertAt(index, length, name, value);
    // }

    replace(target) {
        if (target.statics.blotName !== this.statics.blotName) {
            let item = Parchment.create(this.statics.defaultChild);
            target.moveChildren(item);
            this.appendChild(item);
        }
        super.replace(target);
    }

    // replace(target) {
    //     console.log(this.children);
    //     this.children.head.replace(target);
    // }
}
