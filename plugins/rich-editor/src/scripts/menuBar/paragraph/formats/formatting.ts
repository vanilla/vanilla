/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import HeaderBlot from "@rich-editor/quill/blots/blocks/HeaderBlot";
import { ListItem, ListType, ListValue } from "@rich-editor/quill/blots/blocks/ListBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import { IFormats } from "quill/core";

/**
 * Maps quill state to our format, in a simpler to use object
 * @param activeFormats
 */
export const menuState = (activeFormats: IFormats) => {
    let isParagraphEnabled = true;

    [
        HeaderBlot.blotName,
        BlockquoteLineBlot.blotName,
        CodeBlockBlot.blotName,
        SpoilerLineBlot.blotName,
        ListItem.blotName,
    ].forEach(item => {
        if (item in activeFormats) {
            isParagraphEnabled = false;
        }
    });

    const headerObjectLevel = typeof activeFormats.header === "object" ? activeFormats.header.level : null;

    const listValue: ListValue = activeFormats[ListItem.blotName];
    const hasListFormat = (type: ListType) => {
        return listValue && typeof listValue === "object" && listValue.type === type;
    };

    const listDepth = listValue && typeof listValue === "object" ? listValue.depth : null;

    return {
        paragraph: isParagraphEnabled,
        headings: {
            heading2: activeFormats.header === 2 || headerObjectLevel === 2,
            heading3: activeFormats.header === 3 || headerObjectLevel === 3,
            heading4: activeFormats.header === 4 || headerObjectLevel === 4,
            heading5: activeFormats.header === 5 || headerObjectLevel === 5,
        },
        specialFormats: {
            blockQuote: activeFormats[BlockquoteLineBlot.blotName] === true,
            codeBlock: activeFormats[CodeBlockBlot.blotName] === true,
            spoiler: activeFormats[SpoilerLineBlot.blotName] === true,
        },
        lists: {
            ordered: hasListFormat(ListType.ORDERED),
            unordered: hasListFormat(ListType.BULLETED),
            depth: listDepth,
        },
    };
};

export type IParagraphMenuState = ReturnType<typeof menuState>;
