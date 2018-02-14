import Block from "quill/blots/Block";
import Inline from "quill/blots/Inline";

export default class CodeBlockBlot extends Inline {
    static create(data) {
        const node = super.create(data);
        node.classList.remove('ql-syntax');
        node.classList.add("codeBlock");
        node.innerHTML = data.content;
        return node;
    }

    static value(node) {
        return node.innerHTML;
    }
}

CodeBlockBlot.blotName = 'code-block';
CodeBlockBlot.tagName = 'pre';
