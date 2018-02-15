import CodeBlock from "quill/formats/code";

export default class CodeBlockBlot extends CodeBlock {
    static create() {
        const domNode = super.create();
        domNode.setAttribute('spellcheck', false);
        domNode.classList.add('code');
        domNode.classList.add('isBlock');
        return domNode;
    }
}

CodeBlockBlot.blotName = 'code-block';
CodeBlockBlot.tagName = 'code';
