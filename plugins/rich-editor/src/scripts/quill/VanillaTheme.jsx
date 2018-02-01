import React from "react";
import extend from "extend";
import Emitter from "quill/core/emitter";
import ReactDOM from "react-dom";
import InlineEditorToolbar from "../components/InlineEditorToolbar";
import BaseTheme from "quill/themes/base";

export default class VanillaTheme extends BaseTheme {
    static TOOLBAR_CONFIG = [
        "bold", "italic", "strike", "code", "link",
    ];

    static DEFAULT = {};

    constructor(quill, options) {
        const container = quill.container.closest(".richEditor").querySelector(".richEditorInlineMenu");
        ReactDOM.render(<InlineEditorToolbar quill={quill}/>, container);
        options.modules.toolbar.container = container;

        super(quill, options);
        this.quill.root.classList.add("richEditor-text");
        this.quill.root.classList.add("userContent");

    }

    extendToolbar() {

    }
}
