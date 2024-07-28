/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DeserializeHtml, Nullable } from "@udecode/plate-common";
import { ELEMENT_MENTION } from "@library/vanilla-editor/plugins/mentionPlugin/createMentionPlugin";

export const deserializeMentionHtml: Nullable<DeserializeHtml> | null | undefined = {
    isElement: true,
    rules: [{ validNodeName: "A", validClassName: "atMention" }],
    getNode,
};

function getNode(el) {
    const { dataset, href, pathname } = el;
    let name = dataset.username;

    if (!name) {
        const pathParts = decodeURI(pathname).split("/");
        name = pathParts.pop();
    }

    return {
        type: ELEMENT_MENTION,
        children: [{ text: "" }],
        userID: dataset.userid,
        name,
        url: href,
        domID: `mentionSuggestion${dataset.userid}`,
    };
}
