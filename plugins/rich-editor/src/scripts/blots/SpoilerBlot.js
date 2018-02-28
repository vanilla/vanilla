/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Parchment from "parchment";
import WrapperBlot, { ContentBlot, LineBlot } from "./abstract/WrapperBlot";
import SpoilerButtonBlot from "./SpoilerButtonBlot";

/**
 * Represent a single line of a Spoiler.
 */
export default class SpoilerLineBlot extends LineBlot {
    static blotName = "spoiler-line";
    static className = "spoiler-line";
    static tagName = 'p';
    static parentName = "spoiler-content";
}

/**
 * Represents the full content area of a spoiler.
 */
export class SpoilerContentBlot extends ContentBlot {
    static className = 'spoiler-content';
    static blotName = 'spoiler-content';
    static parentName = 'spoiler';
}

/**
 * Represents the full spoiler. This blot should not be created on it's own. It should always be created upwards
 * through a SpoilerLineBlot.
 */
export class SpoilerWrapperBlot extends WrapperBlot {
    static className = 'spoiler';
    static blotName = 'spoiler';
    static allowedChildren = [...WrapperBlot.allowedChildren, SpoilerButtonBlot];

    static create() {
        const node = super.create();
        node.classList.add("isShowingSpoiler");
        return node;
    }

    /**
     * Attach the toggle button to the spoiler, and set it's event listener.
     */
    attachToggleButton() {
        if (!this.toggleButton) {
            this.toggleButton = Parchment.create("spoiler-button");
            this.insertBefore(this.toggleButton, this.children.head);
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
    }

    optimize(context) {
        super.optimize(context);
    }
}
