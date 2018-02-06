/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import InlineEditorToolbar from "../components/InlineEditorToolbar";
import Theme from "quill/core/theme";
import EditorEmojiPicker from "../components/EditorEmojiPicker";

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
        this.setupKeyboardListeners();
        this.mountToolbar();
        this.mountEmojiMenu();
    }

    setupKeyboardListeners() {
        this.options.modules.keyboard.bindings.tab = false;
        this.options.modules.keyboard.bindings.link = {
            key: "k",
            metaKey: true,
            handler: () => {
                const event = new CustomEvent("LinkShortcut");
                this.quill.root.dispatchEvent(event);
            },
        };
    }

    /**
     * Mount an inline toolbar (react component).
     */
    mountToolbar() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-richEditorInlineMenu");
        ReactDOM.render(<InlineEditorToolbar quill={this.quill}/>, container);
    }

    /**
     * Mount Emoji Menu (react component).
     */

    mountEmojiMenu() {
        const container = this.quill.container.closest(".richEditor").querySelector(".js-emojiHandle");
        ReactDOM.render(<EditorEmojiPicker quill={this.quill}/>, container);
    }
}
