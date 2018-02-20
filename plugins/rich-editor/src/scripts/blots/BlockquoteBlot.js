import ContentBlockBlot from "./ContentBlockBlot";
import { makeWrapperBlot } from "../quill-utilities";

export class BlockquoteContentBlot extends ContentBlockBlot {

    static blotName = "blockquote-content";
    static className = "blockquote-content";
    static tagName = 'div';

    static create() {
        const domNode = super.create();
        domNode.classList.add('blockquote-content');
        domNode.classList.add('blockquote-main');
        return domNode;
    }
}

const BlockquoteBlot = makeWrapperBlot(BlockquoteContentBlot);

BlockquoteBlot.blotName = "blockquote";
BlockquoteBlot.tagName = "blockquote";
BlockquoteBlot.className = "blockquote";

export default BlockquoteBlot;
