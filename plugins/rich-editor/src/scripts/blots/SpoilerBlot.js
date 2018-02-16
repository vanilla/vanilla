import Container from "quill/blots/container";
import Block from 'quill/blots/block';
import Parchment from "parchment";

export class SpoilerContentBlot extends Block {

    static blotName = "spoiler-content";
    static className = "spoiler-content";
    static tagName = 'div';

    static create() {
        const domNode = super.create();
        domNode.classList.add('spoiler-content');
        return domNode;
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

    replace(target) {
        if (target.statics.blotName !== this.statics.blotName) {
            const item = Parchment.create(this.statics.defaultChild);
            target.moveChildren(item);
            this.appendChild(item);
        }
        super.replace(target);
    }
}
