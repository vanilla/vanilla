/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerReducer } from "@dashboard/state/reducerRegistry";
import editorReducer from "@rich-editor/state/editorReducer";
import { onReady } from "@dashboard/application";

onReady(() => {
    registerReducer("editor", editorReducer);
    setupEditor();
    setupCommentEditForm();
});

/**
 * Set up the new discussion form if it exists.
 */
function setupEditor() {
    const discussionFormContainer = document.querySelectorAll(".richEditor");
    if (discussionFormContainer.length > 0) {
        import(/* webpackChunkName: "plugins/rich-editor/js/chunks/mountEditor" */ "@rich-editor/mountEditor").then(
            mountEditor => {
                discussionFormContainer.forEach(mountEditor.default);
            },
        );
    }
}

/**
 * Set up the editor if the someone clicks edit on a form.
 */
function setupCommentEditForm() {
    document.addEventListener("X-EditCommentFormLoaded", event => {
        const container = event.target;
        if (!(container instanceof Element)) {
            return;
        }
        const thing = true;

        const richEditor = container.querySelector(".richEditor");
        if (richEditor) {
            import(/* webpackChunkName: "plugins/rich-editor/js/chunks/mountEditor" */ "@rich-editor/mountEditor").then(
                mountEditor => {
                    mountEditor.default(richEditor);
                },
            );
        }
    });
}
