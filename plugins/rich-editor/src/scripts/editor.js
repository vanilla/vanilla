/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "./Quill/RegisteredQuill";
import * as utility from "@core/utility";
import { ensureHtmlElement } from "@core/dom-utility";

const options = {
    theme: "vanilla",
};

function initializeEditor(bodybox) {
    utility.log("Initializing Rich Editor");
    const initialValue = this.bodybox.value;
    const quill = new Quill(this.container, options);
    bodybox.style.display = "none";

    if (initialValue) {
        utility.log("Setting existing content as contents of editor");
        quill.setContents(JSON.parse(this.initialValue));
    }

    quill.on("text-change", () => {
        bodybox.value = JSON.stringify(quill.getContents()["ops"]);
    });

    this.bodybox.addEventListener("paste", (event) => {
        event.stopPropagation();
        event.preventDefault();

        // Get pasted data via clipboard API
        const clipboardData = event.clipboardData || window.clipboardData;
        const pastedData = clipboardData.getData('Text');
        const delta = JSON.parse(pastedData);
        quill.setContents(delta);
    });
}

/**
 * Mount the editor into a DOM Node.
 *
 * @param {string|Element} containerSelector - The CSS selector or the HTML Element to render into.
 */
export function mountEditor(containerSelector) {
    const container = ensureHtmlElement(containerSelector);
    const bodybox = container.closest("form").querySelector(".BodyBox");

    if (!bodybox) {
        throw new Error("Could not find the BodyBox to mount editor to.");
    }

    const initialFormat = this.bodybox.getAttribute("format") || "rich";

    if (initialFormat === "rich") {
        initializeEditor(bodybox);
    } else {
        throw new Error(`Unsupported initial editor format ${initialFormat}`);
    }
}
