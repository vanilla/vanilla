/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import { ensureHtmlElement } from "@library/dom";
import Editor from "@rich-editor/components/editor/Editor";
import { hasPermission } from "@library/permissions";

/**
 * Mount the editor into a DOM Node.
 *
 * @param containerSelector - The CSS selector or the HTML Element to render into.
 */
export default function mountEditor(containerSelector: string | Element) {
    const container = ensureHtmlElement(containerSelector);
    const bodybox = container.closest("form")!.querySelector(".BodyBox");

    if (!bodybox) {
        throw new Error("Could not find the BodyBox to mount editor to.");
    }

    const initialFormat = bodybox.getAttribute("format") || "Rich";

    if (initialFormat === "Rich") {
        ReactDOM.render(
            <Editor
                legacyTextArea={bodybox as HTMLInputElement}
                isPrimaryEditor={true}
                legacyMode={true}
                allowUpload={hasPermission("uploads.add")}
            />,
            container,
        );
        container.classList.remove("isDisabled");
    } else {
        throw new Error(`Unsupported initial editor format ${initialFormat}`);
    }
}
