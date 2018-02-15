import Code from "quill/formats/code";

class CodeInline extends Code {
    static constructor(domNode) {
        //super();
        console.log("constructor domNode: ", domNode);
    }
}

CodeInline.blotName = 'code-inline';
CodeInline.tagName = 'code';
