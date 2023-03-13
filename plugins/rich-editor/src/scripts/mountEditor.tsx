/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureHtmlElement } from "@vanilla/dom-utils";
import { ForumEditor } from "@rich-editor/editor/ForumEditor";
import React from "react";
import { mountReact } from "@vanilla/react-utils";
import { getMeta } from "@library/utility/appUtils";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";

/**
 * Mount the editor into a DOM Node.
 *
 * @param containerSelector - The CSS selector or the HTML Element to render into.
 */
export default function mountEditor(containerSelector: string | Element, descriptionID?: string) {
    const container = ensureHtmlElement(containerSelector);
    const bodybox = container.closest("form")!.querySelector(".BodyBox");
    const placeholder = bodybox?.getAttribute("placeholder") ?? undefined;
    const isRich2 = getMeta("inputFormat.desktop")?.match(/rich2/i);

    const uploadEnabled = !!container.dataset.uploadenabled;
    const needsHtmlConversion = !!container.dataset.needshtmlconversion;

    if (!bodybox) {
        throw new Error("Could not find the BodyBox to mount editor to.");
    }

    const initialFormat = bodybox.getAttribute("format"); // Could be Rich or rich or any other format

    //This one is a special case when initial format is Rich, current format is Rich2 and we have the toggle to not reinterpret initial format to current
    //So we need to still render Rich. This may not be necessary when we deprecate Rich completely in favour of Rich2
    const forceForumEditor =
        isRich2 && !getMeta("inputFormat.reinterpretPostsAsRich") && initialFormat?.toLowerCase() === "rich";

    if (initialFormat?.match(/rich|2/i)) {
        mountReact(
            isRich2 && !forceForumEditor ? (
                <VanillaEditor
                    initialFormat={initialFormat}
                    needsHtmlConversion={needsHtmlConversion}
                    legacyTextArea={bodybox as HTMLInputElement}
                />
            ) : (
                <ForumEditor
                    placeholder={placeholder}
                    legacyTextArea={bodybox as HTMLInputElement}
                    descriptionID={descriptionID ?? undefined}
                    uploadEnabled={uploadEnabled}
                    needsHtmlConversion={needsHtmlConversion}
                />
            ),
            container,
            () => {
                container.classList.remove("isDisabled");
            },
            { clearContents: true },
        );
    } else {
        throw new Error(`Unsupported initial editor format ${initialFormat}`);
    }
}
