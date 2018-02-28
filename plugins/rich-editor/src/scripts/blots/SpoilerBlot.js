/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Parchment from "parchment";
import WrapperBlot from "./WrapperBlot";
import ClassFormatBlot  from "./ClassFormatBlot";
import { wrappedBlot } from "../quill-utilities";
import SpoilerButtonBlot from "./SpoilerButtonBlot";
import Embed from "quill/blots/embed";

/**
 * Represent a single line of a Spoiler.
 */
class SpoilerLineBlot extends ClassFormatBlot {
    static blotName = "spoiler-line";
    static className = "spoiler-line";
    static tagName = 'p';
    static parentName = "spoiler-content";

    eject() {
        this.moveChildren(this.scroll, this.parent.parent);
    }
}

export default wrappedBlot(SpoilerLineBlot);

/**
 * Represents the full content area of a spoiler.
 */
class ContentBlot extends WrapperBlot {
    static className = 'spoiler-content';
    static blotName = 'spoiler-content';
    static parentName = 'spoiler';
    static allowedChildren = [SpoilerLineBlot];
}

export const SpoilerContentBlot = wrappedBlot(ContentBlot);

export class BlockCursor extends Embed {
    static blotName = "block-cursor";
    static className = "cursor";
    static tagName = "span";

    create() {
        const node = super.create();
        node.setAttribute("contenteditable", false);
        return node;
    }
}

/**
 * Represents the full spoiler. This blot should not be created on it's own. It should always be created upwards
 * through a SpoilerLineBlot.
 */
export class SpoilerWrapperBlot extends WrapperBlot {
    static className = 'spoiler';
    static blotName = 'spoiler';
    static allowedChildren = [...WrapperBlot.allowedChildren, SpoilerButtonBlot, SpoilerLineBlot];

    isOpen = true;

    /**
     * Attach the toggle button to the spoiler, and set it's event listener.
     */
    attachToggleButton() {
        if (!this.toggleButton) {
            this.toggleButton = Parchment.create("spoiler-button");
            this.insertBefore(this.toggleButton, this.children.head);
            this.toggleButton.domNode.addEventListener("click", () => {
                this.isOpen = !this.isOpen;
                this.updateOpenClass();
            });
        }
    }

    /**
     * Update the visibility class on the spoiler to match it's open/closed state.
     */
    updateOpenClass() {
        if (this.domNode.classList.contains("isShowingSpoiler") !== this.isOpen) {
            this.domNode.classList.toggle("isShowingSpoiler");
        }
    }

    /**
     * Remove the blot's toggleButton with it.
     */
    remove() {
        if (this.toggleButton) {
            this.toggleButton.remove();
        }
        super.remove();
    }

    constructor(domNode) {
        super(domNode);
        this.attachToggleButton();
        this.updateOpenClass();
    }

    /**
     * Open the spoiler if something modifies its children when it is closed.
     */
    deleteAt(index, length) {
        if (!this.isOpen) {
            this.isOpen = true;
            this.updateOpenClass();
        }

        super.deleteAt(index, length);
    }

    optimize(context) {
        super.optimize(context);
        this.updateOpenClass();
    }

    /**
     * Open the spoiler if something modifies its children when it is closed.
     */
    insertAt(index, value, ref) {
        if (!this.isOpen) {
            this.isOpen = true;
            this.updateOpenClass();
        }

        super.insertAt(index, value, ref);
    }
}
