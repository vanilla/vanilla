/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import { IFormats, RangeStatic } from "quill/core";
import Formatter from "@rich-editor/quill/Formatter";

export const paragraphFormats = (
    formatter: Formatter,
    lastGoodSelection: RangeStatic,
    afterClickHandler?: () => void,
) => {
    const formatParagraph = () => {
        formatter.paragraph(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const formatBlockquote = (event: React.MouseEvent<HTMLButtonElement>) => {
        formatter.blockquote(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const formatCodeBlock = (event: React.MouseEvent<HTMLButtonElement>) => {
        formatter.codeBlock(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const formatSpoiler = (event: React.MouseEvent<HTMLButtonElement>) => {
        formatter.spoiler(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const formatH2 = () => {
        formatter.h2(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const formatH3 = () => {
        formatter.h3(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    const formatH4 = () => {
        formatter.h4(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };
    const formatH5 = () => {
        formatter.h5(lastGoodSelection);
        afterClickHandler && afterClickHandler();
    };

    return {
        formatParagraph,
        formatBlockquote,
        formatCodeBlock,
        formatSpoiler,
        formatH2,
        formatH3,
        formatH4,
        formatH5,
    };
};

export const getActiveFormats = (activeFormats: IFormats) => {
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
            unordered: true,
            indent: false,
            outdent: false,
        },
    };

    //
    // const paragraphState = {
    //     isActive: isParagraphEnabled,
    //     isDisabled: isParagraphEnabled,
    // };
    //
    // const heading2State = {
    //     isActive: activeFormats.header === 2 || headerObjectLevel === 2,
    //     isDisabled: activeFormats.header === 2 || headerObjectLevel === 2,
    // };
    //
    // const heading3State = {
    //     isActive: activeFormats.header === 3 || headerObjectLevel === 3,
    //     isDisabled: activeFormats.header === 3 || headerObjectLevel === 3,
    // };
    //
    // const heading4State = {
    //     isActive: activeFormats.header === 4 || headerObjectLevel === 4,
    //     isDisabled: activeFormats.header === 4 || headerObjectLevel === 4,
    // };
    // const heading5State = {
    //     isActive: activeFormats.header === 5 || headerObjectLevel === 5,
    //     isDisabled: activeFormats.header === 5 || headerObjectLevel === 5,
    // };
    //
    // const blockQuoteState = {
    //     isActive: activeFormats[BlockquoteLineBlot.blotName] === true,
    //     isDisabled: activeFormats[BlockquoteLineBlot.blotName] === true,
    // };
    //
    // const codeBlockState = {
    //     isActive: activeFormats[CodeBlockBlot.blotName] === true,
    //     isDisabled: activeFormats[CodeBlockBlot.blotName] === true,
    // };
    //
    // const spoilerState = {
    //     isActive: activeFormats[SpoilerLineBlot.blotName] === true,
    //     isDisabled: activeFormats[SpoilerLineBlot.blotName] === true,
    // };
    //
    // return [
    //     {
    //         // label: t("Format as Paragraph"),
    //         isActive: isParagraphEnabled,
    //         isDisabled: isParagraphEnabled,
    //     },
    //     {
    //         // label: t("Format as title (heading 2)"),
    //         isActive: activeFormats.header === 2 || headerObjectLevel === 2,
    //         isDisabled: activeFormats.header === 2 || headerObjectLevel === 2,
    //     },
    //     {
    //         // label: t("Format as title (heading 3)"),
    //         isActive: activeFormats.header === 3 || headerObjectLevel === 3,
    //         isDisabled: activeFormats.header === 3 || headerObjectLevel === 3,
    //     },
    //     {
    //         // label: t("Format as title (heading 4)"),
    //         isActive: activeFormats.header === 4 || headerObjectLevel === 4,
    //         isDisabled: activeFormats.header === 4 || headerObjectLevel === 4,
    //     },
    //     {
    //         // label: t("Format as title (heading 5)"),
    //         isActive: activeFormats.header === 5 || headerObjectLevel === 5,
    //         isDisabled: activeFormats.header === 5 || headerObjectLevel === 5,
    //     },
    //     {
    //         // label: t("Format as blockquote"),
    //         isActive: activeFormats[BlockquoteLineBlot.blotName] === true,
    //         isDisabled: activeFormats[BlockquoteLineBlot.blotName] === true,
    //     },
    //     {
    //         // label: t("Format as code block"),
    //         isActive: activeFormats[CodeBlockBlot.blotName] === true,
    //         isDisabled: activeFormats[CodeBlockBlot.blotName] === true,
    //     },
    //     {
    //         // label: t("Format as spoiler"),
    //         isActive: activeFormats[SpoilerLineBlot.blotName] === true,
    //         isDisabled: activeFormats[SpoilerLineBlot.blotName] === true,
    //     },
    // ];
};
