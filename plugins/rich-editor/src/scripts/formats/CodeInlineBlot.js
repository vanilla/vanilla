import { Code } from "quill/formats/code";

export default class CodeInlineBlot extends Code {
    constructor(domNode) {
        super(domNode);
        domNode.classList.add('code');
        domNode.classList.add('isInline');
    }
}

CodeInlineBlot.blotName = 'code-inline';
CodeInlineBlot.tagName = 'code';
