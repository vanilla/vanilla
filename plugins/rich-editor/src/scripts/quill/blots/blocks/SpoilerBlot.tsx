/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/* tslint:disable:max-classes-per-file */

import ReactDOM from "react-dom";
import React from "react";
import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import ContentBlot from "@rich-editor/quill/blots/abstract/ContentBlot";
import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";
import SpoilerButton from "@rich-editor/quill/components/SpoilerButton";

/**
 * Represent a single line of a Spoiler.
 */
export default class SpoilerLineBlot extends LineBlot {
    public static blotName = "spoiler-line";
    public static className = "spoiler-line";
    public static tagName = "p";
    public static parentName = "spoiler-content";
}

/**
 * Represents the full content area of a spoiler.
 */
export class SpoilerContentBlot extends ContentBlot {
    public static className = "spoiler-content";
    public static blotName = "spoiler-content";
    public static parentName = "spoiler";
}

/**
 * Represents the full spoiler. This blot should not be created on it's own. It should always be created upwards
 * through a SpoilerLineBlot.
 */
export class SpoilerWrapperBlot extends WrapperBlot {
    public static className = "spoiler";
    public static blotName = "spoiler";
    public static allowedChildren = [...WrapperBlot.allowedChildren];

    public static create(value) {
        const node = super.create(value);
        node.classList.add("isShowingSpoiler");
        return node;
    }

    constructor(domNode) {
        super(domNode);

        // We need to synchronously create a spot for this button to be rendered. Otherwise react's async rendering
        // could cause the button to be rendered after quill inserts the first editable line.
        // The button ALWAYS needs to be first.
        const button = document.createElement("div");
        this.domNode.appendChild(button);
        ReactDOM.render(<SpoilerButton />, button);
    }

    public attach() {
        super.attach();
    }

    public optimize(context) {
        super.optimize(context);
    }
}
