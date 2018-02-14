import Block from "quill/blots/Block";
import Inline from "quill/blots/Inline";

// export default class CodeInlineBlot extends Inline {
//     static create(data) {
//         const node = super.create(data);
//         node.classList.add("code");
//         node.classList.add("codeInline");
//         node.innerHTML = data.content;
//         return node;
//     }
//
//     static value(node) {
//         return node.innerHTML;
//     }
//
// }
//
// CodeInlineBlot.blotName = 'code-inline';
// CodeInlineBlot.tagName = 'code';


class CodeInline extends Inline {
    static create(data) {
        const node = super.create(data);
        node.classList.add("code");
        node.classList.add("codeInline");
        node.innerHTML = data.content;
        return node;
    }
}

CodeInline.blotName = 'code-inline';
CodeInline.tagName = 'code';
