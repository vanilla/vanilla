/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import events from "@core/events";
import { mountEditor } from "./editor";

events.onVanillaReady(() => {
    setupEditor();
    setupCommentForm();
});

/**
 * Set up the new discussion form if it exists.
 */
function setupEditor() {
    const discussionFormContainer = document.querySelectorAll(".js-richText");

    discussionFormContainer.forEach(mountEditor);
}

function setupCommentForm() {
    $(document).on("EditCommentFormLoaded", (event, container) => {
        const $commentFormContainer = $(container).find(".js-richText");
        if ($commentFormContainer) {
            mountEditor($commentFormContainer[0]);
        }
    });
}
