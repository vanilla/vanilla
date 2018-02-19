import CodeBlock from "quill/formats/code";
import { makeWrapperBlot } from "../quill-utilities";
import {SpoilerContentBlot} from "./SpoilerBlot";

export class CodeBlockContentsBlot extends CodeBlock {

    static blotName = 'codeblock-contents';
    static className = 'code';
    static tagName = 'code';

    static create() {
        const domNode = super.create();
        domNode.setAttribute('spellcheck', false);
        domNode.classList.add('code');
        domNode.classList.add('isBlock');
        return domNode;
    }

    static formats(domNode) {
        return domNode.tagName === this.tagName ? undefined : super.formats(domNode);
    }

    remove() {
        if (this.prev == null && this.next == null) {
            this.parent.remove();
        } else {
            super.remove();
        }
    }

    replaceWith(name, value) {
        this.parent.isolate(this.offset(this.parent), this.length());
        if (name === this.parent.statics.blotName) {
            this.parent.replaceWith(name, value);
            return this;
        } else {
            this.parent.unwrap();
            return super.replaceWith(name, value);
        }
    }
}

const CodeBlockBlot = makeWrapperBlot(CodeBlockContentsBlot);

CodeBlockBlot.blotName = "code-block";
CodeBlockBlot.tagName = "pre";

export default CodeBlockBlot;
