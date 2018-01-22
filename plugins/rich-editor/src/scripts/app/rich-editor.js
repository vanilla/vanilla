/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import events from "@core/events";
import RichEditor from "../RichEditor";

events.onVanillaReady(() => {
    setupNewDiscussionForm();
    setupCommentForm();
});

/**
 * Set up the new discussion form if it exists.
 */
function setupNewDiscussionForm() {
    const discussionFormContainer = document.querySelector("#DiscussionForm .js-richText");

    if (discussionFormContainer) {
        new RichEditor(discussionFormContainer);
    }
}

function setupCommentForm() {
    const commentFormContainer = document.querySelector("#Form_Comment .js-richText");

    if (commentFormContainer) {
        new RichEditor(commentFormContainer);
    }
}
