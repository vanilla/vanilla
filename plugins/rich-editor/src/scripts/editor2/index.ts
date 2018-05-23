/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "../quill/index";
import * as utility from "@dashboard/utility";
import { ensureHtmlElement } from "@dashboard/dom";

const options = {
    theme: "vanilla",
};

function initializeEditor(bodybox, container) {
    utility.log("Initializing Rich Editor");
    const initialValue = bodybox.value;
    const quill = new Quill(container, options);
    bodybox.style.display = "none";

    if (initialValue) {
        utility.log("Setting existing content as contents of editor");
        quill.setContents(JSON.parse(initialValue));
    }

    quill.on("text-change", () => {
        bodybox.value = JSON.stringify(quill.getContents().ops);
    });

    bodybox.addEventListener("paste", event => {
        event.stopPropagation();
        event.preventDefault();

        // Get pasted data via clipboard API
        const clipboardData = event.clipboardData || window.clipboardData;
        const pastedData = clipboardData.getData("Text");
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
    const bodybox = container.closest("form")!.querySelector(".BodyBox");

    if (!bodybox) {
        throw new Error("Could not find the BodyBox to mount editor to.");
    }

    const initialFormat = bodybox.getAttribute("format") || "Rich";

    if (initialFormat === "Rich") {
        initializeEditor(bodybox, container);
    } else {
        throw new Error(`Unsupported initial editor format ${initialFormat}`);
    }
}
