/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import uniqueId from "lodash/uniqueId";
import { ensureHtmlElement } from "@dashboard/dom";
import Editor from "@rich-editor/components/editor/Editor";

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
    const editorID = uniqueId("editor-");
    const descriptionID = editorID + "-description";

    if (initialFormat === "Rich") {
        ReactDOM.render(
            <Editor
                editorID={editorID}
                editorDescriptionID={descriptionID}
                legacyTextArea={bodybox as HTMLInputElement}
                isPrimaryEditor={true}
            />,
            container,
        );
        container.classList.remove("isDisabled");
    } else {
        throw new Error(`Unsupported initial editor format ${initialFormat}`);
    }
}
