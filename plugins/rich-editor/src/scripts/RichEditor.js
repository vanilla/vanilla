/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import Quill from "quill";
import * as utility from "@core/utility";
import AutoLinker from "./AutoLinker";

Quill.register('modules/autoLinker', AutoLinker);

const toolbarOptions = [
    ["bold", "italic", "underline", "strike"], // toggled buttons
    ['link', 'image'], // Links and images
    ["blockquote", "code-block"], // Blocks
    [{ header: 1 }, { header: 2 }], // custom button values
    [{ list: "ordered"}, { list: "bullet" }],
    [{ indent: "-1"}, { indent: "+1" }], // outdent/indent
    [{ header: [1, 2, false] }],
    ["clean"], // remove formatting button
];

const options = {
    modules: {
        toolbar: toolbarOptions,
        autoLinker: true,
    },
    placeholder: "Create a new post...",
    theme: "bubble",
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

        if (this.initialValue) {
            utility.log("Setting existing content as contents of editor");
            this.editor.setContents(JSON.parse(this.initialValue));
        }

        this.editor.on("text-change", this.synchronizeDelta.bind(this));
        this.initSpaceToLinkConverter();
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

    initSpaceToLinkConverter() {
        // @ts-ignore
        this.editor.keyboard.addBinding({
            collapsed: true,
            key: ' ',
            prefix: /https?:\/\/[^\s]+/, // call handler only when matched this regex
            handler: (() => {
                let prevOffset = 0;
                return (range) => {
                    let url;
                    const regex = /https?:\/\/[^\s]+/g;
                    const text = this.editor.getText(prevOffset, range.index);
                    const match = text.match(regex);
                    if (match === null) {
                        prevOffset = range.index;
                        return true;
                    }
                    if (match.length > 1) {
                        url = match[match.length - 1];
                    } else {
                        url = match[0];
                    }
                    const ops = [];
                    ops.push({retain: range.index - url.length});
                    ops.push({delete: url.length});
                    ops.push({insert: url, attributes: {link: url}});
                    this.editor.updateContents({ops});
                    prevOffset = range.index;
                    return true;
                };
            }),
        });
    }
}
