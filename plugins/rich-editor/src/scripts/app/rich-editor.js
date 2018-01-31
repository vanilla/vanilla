/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import events from "@core/events";
import RichEditor from "../RichEditor";

events.onVanillaReady(() => {
    setupEditor();
    setupCommentForm();
});

/**
 * Set up the new discussion form if it exists.
 */
function setupEditor() {
    const discussionFormContainer = document.querySelectorAll(".js-richText");

    discussionFormContainer.forEach(container => {
        new RichEditor(container);
    });
}

function setupCommentForm() {
    // const commentFormContainer = document.querySelector("#Form_Comment .js-richText");

    // if (commentFormContainer) {
    //     new RichEditor(commentFormContainer);
    // }

    $(document).on("EditCommentFormLoaded", (event, container) => {
        const $commentFormContainer = $(container).find(".js-richText");
        if ($commentFormContainer) {
            new RichEditor($commentFormContainer[0]);
        }
    });
}
