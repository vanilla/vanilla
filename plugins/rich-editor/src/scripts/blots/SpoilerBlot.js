import ContentBlockBlot from "./ContentBlockBlot";
import { makeWrapperBlot } from "../quill-utilities";

export class SpoilerContentBlot extends ContentBlockBlot {

    static blotName = "spoiler-content";
    static className = "spoiler-content";
    static tagName = 'div';

    static create() {
        const domNode = super.create();
        domNode.classList.add('spoiler-content');
        return domNode;
    }
}

const SpoilerBlot = makeWrapperBlot(SpoilerContentBlot);

SpoilerBlot.blotName = "spoiler";
SpoilerBlot.className = "spoiler";
SpoilerBlot.tagName = "div";

export default SpoilerBlot;
