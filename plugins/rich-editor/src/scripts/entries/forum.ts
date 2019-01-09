/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import editorReducer from "@rich-editor/state/editorReducer";
import { registerReducer } from "@library/state/reducerRegistry";
import { onReady, onContent } from "@library/application";
import "../../scss/editor.scss";

onReady(() => {
    registerReducer("editor", editorReducer);
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
        const mountEditor = await import(/* webpackChunkName: "mountEditor" */ "@rich-editor/mountEditor");
        editorMountPoints.forEach(mountPoint => {
            if (!mountPoint.classList.contains(MOUNTED_CLASS)) {
                mountPoint.classList.add(MOUNTED_CLASS);
                mountEditor.default(mountPoint);
            }
        });
    }
}
