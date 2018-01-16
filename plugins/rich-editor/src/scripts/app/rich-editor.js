/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import events from "@core/events";
import RichEditor from "../RichEditor";

events.onVanillaReady(() => {
    setupNewDiscussionForm();
});

/**
 * Set up the new discussion form if it exists.
 */
function setupNewDiscussionForm() {
    const discussionFormContainer = document.querySelector("#DiscussionForm .bodybox-wrap .TextBoxWrapper");

    if (discussionFormContainer) {
        new RichEditor(discussionFormContainer);
    }
}
