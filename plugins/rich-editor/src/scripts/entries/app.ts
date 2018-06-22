/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import reducerRegistry from "@dashboard/state/reducerRegistry";
import editorReducer from "@rich-editor/state/editorReducer";
import mountEditor from "@rich-editor/mountEditor";

reducerRegistry.register("editor", editorReducer);
setupEditor();
setupCommentEditForm();

/**
 * Set up the new discussion form if it exists.
 */
function setupEditor() {
    const discussionFormContainer = document.querySelectorAll(".richEditor");
    if (discussionFormContainer.length > 0) {
        discussionFormContainer.forEach(mountEditor);
    }
}

/**
 * Set up the editor if the someone clicks edit on a form.
 */
function setupCommentEditForm() {
    $(document).on("EditCommentFormLoaded", (event, container) => {
        const $commentFormContainer = $(container).find(".richEditor");
        if ($commentFormContainer.length > 0) {
            mountEditor($commentFormContainer[0]);
        }
    });
}
