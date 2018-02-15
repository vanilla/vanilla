import CodeBlock from "quill/formats/code";

export default class CodeBlockBlot extends CodeBlock {
    static create(value) {
        const domNode = super.create(value);
        domNode.setAttribute('spellcheck', false);
        domNode.classList.add('codeBlock');

        const code = document.createElement('code');
        code.classList.add('code');
        code.classList.add('isBlock');
        code.innerHTML = value;
        domNode.appendChild(code);

        return domNode;
    }
}

CodeBlockBlot.blotName = 'code-block';
CodeBlockBlot.tagName = 'pre';
