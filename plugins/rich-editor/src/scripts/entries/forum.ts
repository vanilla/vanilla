/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import editorReducer from "@rich-editor/state/editorReducer";
import { registerReducer } from "@library/state/reducerRegistry";
import { onReady } from "@library/application";
import "../../scss/editor.scss";

onReady(() => {
    registerReducer("editor", editorReducer);
    void setupEditor();
    void setupCommentEditForm();
});

/**
 * Set up the new discussion form if it exists.
 */
async function setupEditor() {
    const discussionFormContainer = document.querySelectorAll(".richEditor");
    if (discussionFormContainer.length > 0) {
        const mountEditor = await import(/* webpackChunkName: "mountEditor" */ "@rich-editor/mountEditor");
        discussionFormContainer.forEach(mountEditor.default);
    }
}

/**
 * Set up the editor if the someone clicks edit on a form.
 */
async function setupCommentEditForm() {
    document.addEventListener("X-EditCommentFormLoaded", async event => {
        const container = event.target;
        if (!(container instanceof Element)) {
            return;
        }

        const richEditor = container.querySelector(".richEditor");
        if (richEditor) {
            const mountEditor = await import(/* webpackChunkName: "mountEditor" */ "@rich-editor/mountEditor");
            mountEditor.default(richEditor);
        }
    });
}
