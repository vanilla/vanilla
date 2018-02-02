/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import Quill from "quill";
import InlineEditorToolbar from "../components/InlineEditorToolbar";
import BaseTheme from "quill/themes/base";

export default class VanillaTheme extends BaseTheme {
    static TOOLBAR_CONFIG = [
        "bold", "italic", "strike", "code", "link",
    ];

    static DEFAULT = {};

    /**
     *
     * @param {Quill} quill
     * @param options
     */
    constructor(quill, options) {
        const container = quill.container.closest(".richEditor").querySelector(".richEditorInlineMenu");
        ReactDOM.render(<InlineEditorToolbar quill={quill}/>, container);
        options.modules.toolbar.container = container;

        super(quill, options);
        quill.root.classList.add("richEditor-text");
        quill.root.classList.add("userContent");

        // Nullify the tab key.
        options.modules.keyboard.bindings = {
            key: 9, // Tab key
            handler: () => {},
        };
    }

    extendToolbar() {

    }
}
