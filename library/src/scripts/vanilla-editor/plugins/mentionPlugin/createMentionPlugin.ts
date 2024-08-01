/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IMentionSuggestionData } from "@library/editor/pieces/MentionSuggestion";
import { MyEditor, MyValue } from "@library/vanilla-editor/typescript";
import { createMentionPlugin as createPlateMentionPlugin, MentionPlugin, withMention } from "@udecode/plate-mention";
import { MentionElement } from "@library/vanilla-editor/plugins/mentionPlugin/MentionElement";
import { deserializeMentionHtml } from "@library/vanilla-editor/plugins/mentionPlugin/deserializeMentionHtml";
import { TComboboxItemWithData } from "@udecode/plate-combobox";
import { insertMentionData } from "@library/vanilla-editor/plugins/mentionPlugin/insertMentionData";

export const ELEMENT_MENTION = "@";

export function createMentionPlugin() {
    return createPlateMentionPlugin<MentionPlugin<IMentionSuggestionData>, MyValue, MyEditor>({
        key: ELEMENT_MENTION,
        component: MentionElement,
        // pasted HTML from another post will be deserialized into this element
        deserializeHtml: deserializeMentionHtml,
        props: (oldProps) => {
            return {
                prefix: oldProps.element.type, //use "@" as the prefix
            };
        },
        options: {
            insertSpaceAfterMention: true,
            createMentionNode: (item: TComboboxItemWithData<IMentionSuggestionData>) => {
                return {
                    ...item.data,
                    value: "", // Useless to us.
                };
            },
        },
        withOverrides: (editor, { options }) => {
            // custom handler for pasting data
            editor.insertData = (data) => insertMentionData(editor, data);

            // add the custom overrides to the pre-existing plugin overrides
            return withMention<MyValue, MyEditor>(editor, { options } as any);
        },
    });
}
