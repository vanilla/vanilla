/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import EmbedBlot from "quill/blots/embed";
import { formatUrl, makeProfileUrl } from "@dashboard/application";
import { IMentionSuggestionData } from "@rich-editor/components/toolbars/pieces/MentionSuggestion";

/**
 * A blot that represents a completed mention.
 */
export default class MentionBlot extends EmbedBlot {
    public static blotName = "mention";
    public static className = "atMention";
    public static tagName = "a";

    public static create(data: IMentionSuggestionData) {
        const node = super.create(data) as HTMLLinkElement;
        node.textContent = "@" + data.name;
        node.dataset.userid = data.userID.toString();
        node.dataset.username = data.name;
        node.href = makeProfileUrl(data.name);
        return node;
    }

    public static value(node: HTMLLinkElement): Partial<IMentionSuggestionData> {
        return {
            name: node.dataset.username,
            userID: parseInt(node.dataset.userid || "", 10),
        };
    }
}
