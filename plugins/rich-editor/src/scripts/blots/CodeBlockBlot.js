import CodeBlock from "quill/formats/code";

export default class CodeBlockBlot extends CodeBlock {

    static blotName = 'code-block';
    static tagName = 'code';

    static create() {
        const domNode = super.create();
        domNode.setAttribute('spellcheck', false);
        domNode.classList.add('code');
        domNode.classList.add('isBlock');
        return domNode;
    }
}
