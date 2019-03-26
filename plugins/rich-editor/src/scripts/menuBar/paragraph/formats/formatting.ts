/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import { IFormats, RangeStatic } from "quill/core";
import Formatter from "@rich-editor/quill/Formatter";

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
        formatter.h5(lastGoodSelection); // TODO
        afterClickHandler && afterClickHandler();
    };

    const listOrdered = () => {
        formatter.h5(lastGoodSelection); // TODO
        afterClickHandler && afterClickHandler();
    };

    const listIndent = () => {
        formatter.h5(lastGoodSelection); // TODO
        afterClickHandler && afterClickHandler();
    };

    const listOutdent = () => {
        formatter.h5(lastGoodSelection); // TODO
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

/**
 * Maps quill state to our format, in a simpler to use object
 * @param activeFormats
 */
export const menuState = (activeFormats: IFormats) => {
    // console.log("activeFormats: ", activeFormats);
    let isParagraphEnabled = true;

    ["header", BlockquoteLineBlot.blotName, CodeBlockBlot.blotName, SpoilerLineBlot.blotName].forEach(item => {
        if (item in activeFormats) {
            isParagraphEnabled = false;
        }
    });

    const headerObjectLevel = typeof activeFormats.header === "object" ? activeFormats.header.level : null;

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
            ordered: false,
            unordered: false,
            indent: false,
            outdent: false,
        },
    };
};
