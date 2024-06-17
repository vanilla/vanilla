/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DeserializeHtml, Nullable } from "@udecode/plate-common";

export const deserializeMentionHtml: Nullable<DeserializeHtml> | null | undefined = {
    isElement: true,
    rules: [{ validNodeName: "A", validClassName: "atMention" }],
    getNode,
};

function getNode(el) {
    const { dataset, href } = el;

    return {
        type: "@",
        children: [{ text: "" }],
        userID: dataset.userid,
        name: dataset.username,
        url: href,
        domID: `mentionSuggestion${dataset.userid}`,
    };
}
