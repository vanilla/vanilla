import { Code } from "quill/formats/code";

export default class CodeInlineBlot extends Code {
    static blotName = 'code-inline';
    static tagName = 'code';
    static className = 'code-inline';

    constructor(domNode) {
        super(domNode);
        domNode.classList.add('code');
        domNode.classList.add('code-inline');
        domNode.classList.add('isInline');
    }
}
