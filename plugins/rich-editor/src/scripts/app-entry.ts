/*
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { mountEditor } from "./editor";
import initImageEmbed from "./embeds/image";
import initTwitterEmbed from "./embeds/twitter";
import initVideoEmbed from "./embeds/video";
import initLinkEmbed from "./embeds/link";

initImageEmbed();
initTwitterEmbed();
initVideoEmbed();
initLinkEmbed();
setupEditor();
setupCommentEditForm();

/**
 * Set up the new discussion form if it exists.
 */
function setupEditor() {
    const discussionFormContainer = document.querySelectorAll(".js-richText");

    discussionFormContainer.forEach(mountEditor);
}

/**
 * Set up the editor if the someone clicks edit on a form.
 */
function setupCommentEditForm() {
    $(document).on("EditCommentFormLoaded", (event, container) => {
        const $commentFormContainer = $(container).find(".js-richText");
        if ($commentFormContainer) {
            mountEditor($commentFormContainer[0]);
        }
    });
}
