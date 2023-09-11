/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { MyEditor } from "@library/vanilla-editor/typescript";
import { floatingLinkActions, getEditorString } from "@udecode/plate-headless";

/**
 * We're using this instead of the one in @udecode/plate because we want it to work with our embeds, rather than just
 * Normal links.
 */
export function triggerFloatingLinkEdit(editor: MyEditor) {
    const entry = queryRichLink(editor);
    if (!entry) {
        return;
    }

    const { element, path } = entry;

    if (!element.url) {
        // Nothing we can do.
        // The data source type is likely a file or an image.
        return;
    }

    let text = getEditorString(editor, path);

    floatingLinkActions.url(element.url);

    floatingLinkActions.newTab(true);

    if (text === element.url) {
        text = "";
    }

    floatingLinkActions.text(text);

    floatingLinkActions.isEditing(true);

    return true;
}
