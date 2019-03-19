/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import {
    blockquote,
    codeBlock,
    heading2,
    heading3,
    heading4,
    heading5,
    pilcrow,
    spoiler,
} from "@library/icons/editorIcons";
import { t } from "@library/utility/appUtils";
import { RangeStatic } from "quill/core";
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

export const menuItemData = (formatter: Formatter, lastGoodSelection: RangeStatic, afterClickHandler?: () => void) => {
    const { activeFormats } = this.props;
    let isParagraphEnabled = true;
    ["header", BlockquoteLineBlot.blotName, CodeBlockBlot.blotName, SpoilerLineBlot.blotName].forEach(item => {
        if (item in activeFormats) {
            isParagraphEnabled = false;
        }
    });

    const headerObjectLevel = typeof activeFormats.header === "object" ? activeFormats.header.level : null;

    const formats = paragraphFormats(formatter, lastGoodSelection, afterClickHandler);

    return [
        {
            icon: pilcrow(),
            label: t("Format as Paragraph"),
            onClick: formats.formatParagraph,
            isActive: isParagraphEnabled,
            isDisabled: isParagraphEnabled,
        },
        {
            icon: heading2(),
            label: t("Format as title (heading 2)"),
            onClick: this.formatH2,
            isActive: activeFormats.header === 2 || headerObjectLevel === 2,
            isDisabled: activeFormats.header === 2 || headerObjectLevel === 2,
        },
        {
            icon: heading3(),
            label: t("Format as title (heading 3)"),
            onClick: this.formatH3,
            isActive: activeFormats.header === 3 || headerObjectLevel === 3,
            isDisabled: activeFormats.header === 3 || headerObjectLevel === 3,
        },
        {
            icon: heading4(),
            label: t("Format as title (heading 4)"),
            onClick: this.formatH2,
            isActive: activeFormats.header === 4 || headerObjectLevel === 4,
            isDisabled: activeFormats.header === 4 || headerObjectLevel === 4,
        },
        {
            icon: heading5(),
            label: t("Format as title (heading 5)"),
            onClick: this.formatH3,
            isActive: activeFormats.header === 5 || headerObjectLevel === 5,
            isDisabled: activeFormats.header === 5 || headerObjectLevel === 5,
        },
        {
            icon: blockquote(),
            label: t("Format as blockquote"),
            onClick: formats.formatBlockquote,
            isActive: activeFormats[BlockquoteLineBlot.blotName] === true,
            isDisabled: activeFormats[BlockquoteLineBlot.blotName] === true,
        },
        {
            icon: codeBlock(),
            label: t("Format as code block"),
            onClick: formats.formatCodeBlock,
            isActive: activeFormats[CodeBlockBlot.blotName] === true,
            isDisabled: activeFormats[CodeBlockBlot.blotName] === true,
        },
        {
            icon: spoiler("richEditorButton-icon"),
            label: t("Format as spoiler"),
            onClick: formats.formatSpoiler,
            isActive: activeFormats[SpoilerLineBlot.blotName] === true,
            isDisabled: activeFormats[SpoilerLineBlot.blotName] === true,
        },
    ];
};
