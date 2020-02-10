/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady, onContent } from "@library/utility/appUtils";

onReady(() => {
    void setupEditor();
});
onContent(setupEditor);

const MOUNTED_CLASS = "js-isMounted";

/**
 * Set up the new discussion form if it exists.
 */
async function setupEditor() {
    const editorMountPoints = document.querySelectorAll(".richEditor");
    if (editorMountPoints.length > 0) {
        const body = document.getElementsByTagName("body");
        if (body) {
            body[0].classList.add("hasRichEditor");
        }
        const mountEditor = await import(/* webpackChunkName: "mountEditor" */ "@rich-editor/mountEditor");
        editorMountPoints.forEach(mountPoint => {
            if (!mountPoint.classList.contains(MOUNTED_CLASS)) {
                mountPoint.classList.add(MOUNTED_CLASS);
                const popup = mountPoint.closest(".Popup");
                if (popup) {
                    popup.classList.add("hasRichEditor");
                }

                mountEditor.default(mountPoint);
            }
        });
    }
}
