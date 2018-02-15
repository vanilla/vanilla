import Container from "quill/blots/container";
import CodeBlock from "quill/formats/code";
import Parchment from "parchment";

export default class CodeBlockBlot extends Container {

    static blotName = 'code-block';
    static tagName = 'pre';
    static scope = Parchment.Scope.BLOCK_BLOT;
    static defaultChild = 'code-block-child';
    static allowedChildren = [CodeBlock];

    static create() {
        const domNode = super.create();
        domNode.setAttribute('spellcheck', false);
        domNode.classList.add('code');
        domNode.classList.add('isBlock');
        return domNode;
    }

    replace(target) {
        if (target.statics.blotName !== this.statics.blotName) {
            let item = Parchment.create(this.statics.defaultChild);
            target.moveChildren(item);
            this.appendChild(item);
        }
        super.replace(target);
    }
}

export class CodeBlockChildBlot extends CodeBlock {

    static blotName = "code-block-child";
    static tagName = 'code';
}
