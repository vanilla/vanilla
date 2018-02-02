/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import InlineEditorToolbar from "../components/InlineEditorToolbar";
import Theme from "quill/core/theme";

export default class VanillaTheme extends Theme {
    static TOOLBAR_CONFIG = [
        "bold", "italic", "strike", "code", "link",
    ];

    static DEFAULTS = {
        modules: {
            toolbar: false,
        },
        placeholder: "Create a new post...",
    };

    /**
     * Constructor.
     *
     * @param {Quill} quill - The quill instance the theme is applying to.
     * @param {QuillOptionsStatic} options - The current options for the instance.
     */
    constructor(quill, options) {
        super(quill, options);
        this.quill.root.classList.add("richEditor-text");
        this.quill.root.classList.add("userContent");
        this.setupTabBehaviour();
        this.mountToolbar();
    }

    /**
     * Nullify the tab key.
     */
    setupTabBehaviour() {
        // Nullify the tab key.
        this.options.modules.keyboard.bindings = {
            tab: false,
        };
    }

    /**
     * Mount an inline toolbar (react component).
     */
    mountToolbar() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-richEditorInlineMenu");
        ReactDOM.render(<InlineEditorToolbar quill={this.quill}/>, container);
    }
}
