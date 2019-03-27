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
import Formatter from "@rich-editor/quill/Formatter";
import { IFormats, RangeStatic } from "quill/core";

/**
 * Maps quill functions to functions
 * @param formatter
 * @param lastGoodSelection
 * @param afterClickHandler
 */
export const paragraphFormats = (
    formatter: Formatter,
    lastGoodSelection: RangeStatic,
    afterClickHandler?: () => void,
) => {
    const paragraph = () => {
        formatter.paragraph(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const blockquote = () => {
        formatter.blockquote(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const codeBlock = () => {
        formatter.codeBlock(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const spoiler = () => {
        formatter.spoiler(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const h2 = () => {
        formatter.h2(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const h3 = () => {
        formatter.h3(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const h4 = () => {
        formatter.h4(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const h5 = () => {
        formatter.h5(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const listUnordered = () => {
        formatter.bulletedList(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const listOrdered = () => {
        formatter.orderedList(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const listIndent = () => {
        formatter.indentList(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const listOutdent = () => {
        formatter.outdentList(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    return {
        paragraph,
        blockquote,
        codeBlock,
        spoiler,
        listUnordered,
        listOrdered,
        listIndent,
        listOutdent,
        h2,
        h3,
        h4,
        h5,
    };
};

export type IParagraphFormatter = ReturnType<typeof paragraphFormats>;

/**
 * Maps quill state to our format, in a simpler to use object
 * @param activeFormats
 */
export const menuState = (activeFormats: IFormats) => {
    // console.log("activeFormats: ", activeFormats);
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

    const canOutdent = () => {
        return listValue && typeof listValue === "object" && listValue.depth > 0;
    };

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
            indent: false,
            outdent: canOutdent(),
        },
    };
};

export type IParagraphMenuState = ReturnType<typeof menuState>;
