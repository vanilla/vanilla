/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "quill/quill";
import EmojiBlot from "./blots/EmojiBlot.js";
import ImageBlot from "./blots/ImageBlot.js";
import VanillaTheme from "./quill/VanillaTheme";
import * as utility from "@core/utility";

// Blots
Quill.register(EmojiBlot);
Quill.register(ImageBlot);

// Theme
Quill.register("themes/vanilla", VanillaTheme);

// Temporary function
const makeElement = (tag, attrs) => {
    const elem = document.createElement(tag);
    Object.keys(attrs).forEach(key => elem[key] = attrs[key]);
    return elem;
};


const options = {
    theme: "vanilla",
};

export default class RichEditor {

    initialFormat = "rich";
    initialValue = "";

    /** @type {HTMLFormElement} */
    form;

    /**
     * Create a new RichEditor.
     *
     * @param {string|Element} containerSelector - The CSS selector or the container to render into.
     */
    constructor(containerSelector) {
        if (typeof containerSelector === "string") {
            this.container = document.querySelector(containerSelector);
            if (!this.container) {
                if (!this.container) {
                    throw new Error(`Editor container ${containerSelector} could not be found. Rich Editor could not be started.`);
                }
            }
        } else if (containerSelector instanceof HTMLElement) {
            this.container = containerSelector;
        }

        // Hijack the form submit
        const form = this.container.closest("form");
        this.bodybox = form.querySelector(".BodyBox");

        if (!this.bodybox) {
            throw new Error("Could not find the BodyBox inside of the form.");
        }

        this.initialFormat = this.bodybox.getAttribute("format") || "Rich";
        this.initialValue = this.bodybox.value;

        if (this.initialFormat === "Rich") {
            this.initializeWithRichFormat();
        } else {
            this.initializeOtherFormat();
        }
    }

    initializeWithRichFormat() {
        utility.log("Initializing Rich Editor");
        this.editor = new Quill(this.container, options);
        this.bodybox.style.display = "none";

        if (this.initialValue) {
            utility.log("Setting existing content as contents of editor");
            this.editor.setContents(JSON.parse(this.initialValue));
        }

        this.editor.on("text-change", this.synchronizeDelta.bind(this));
        
        // const insertEmoji = () => {
        //     const editorSelection = this.editor.getSelection();
        //     const emoji = '😊';
        //     let range = this.editor.getSelection(true);
        //     this.editor.insertEmbed(range.index, 'emoji', {
        //         'emojiChar': emoji
        //     }, Quill.sources.USER);
        //     this.editor.setSelection(range.index + 1, Quill.sources.SILENT);
        //
        // };
        // document.querySelector(".emojiButton").addEventListener("click", insertEmoji);
        //
        // const insertImage = () => {
        //     let range = this.editor.getSelection(true);
        //     this.editor.insertEmbed(range.index, 'image', {
        //         alt: 'Quill Cloud',
        //         url: 'http://stephane.local/uploads/userpics/966/pNOH8FCLAMG82.jpg'
        //     }, Quill.sources.USER);
        //     this.editor.setSelection(range.index + 1, Quill.sources.SILENT);
        // };
        // document.querySelector(".imageButton").addEventListener("click", insertImage);

    }

    /**
     * For compatibility with the legacy base theme's javascript the Quill Delta needs to always be in the main form
     * as a hidden input (Because we aren't overriding the submit)
     */
    synchronizeDelta() {
        this.bodybox.value = JSON.stringify(this.editor.getContents()["ops"]);
    }

    initializeOtherFormat() {

        // TODO: check if we can convert from a format

        return;
    }
}
