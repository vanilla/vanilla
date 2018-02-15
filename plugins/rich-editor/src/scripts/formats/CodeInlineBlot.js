import {Code} from "quill/formats/code";

export default class CodeInline extends Code {
    constructor(domNode) {
        super(domNode);
        domNode.classList.add('code');
        domNode.classList.add('codeInline');
        console.log("HERE");
    }
}

CodeInline.blotName = 'code-inline';
CodeInline.tagName = 'code';
