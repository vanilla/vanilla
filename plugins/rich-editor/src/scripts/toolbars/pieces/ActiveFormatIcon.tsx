/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IFormats } from "quill/core";
import HeadingBlot from "quill/formats/header";
import {
    heading2,
    heading3,
    blockquote,
    codeBlock,
    spoiler,
    pilcrow,
    heading4,
    heading5,
    listOrdered,
    listUnordered,
} from "@library/icons/editorIcons";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";

interface IProps {
    activeFormats: IFormats;
}

export default class ActiveFormatIcon extends React.Component<IProps, {}> {
    public render() {
        const { activeFormats } = this.props;
        let icon = pilcrow();
        if (activeFormats.headings.heading2) {
            icon = heading2();
        } else if (activeFormats.headings.heading3) {
            icon = heading3();
        } else if (activeFormats.headings.heading4) {
            icon = heading4();
        } else if (activeFormats.headings.heading5) {
            icon = heading5();
        } else if (activeFormats.lists.ordered) {
            icon = listOrdered();
        } else if (activeFormats.lists.unordered) {
            icon = listUnordered();
        } else if (activeFormats.specialFormats.blockQuote) {
            icon = blockquote();
        } else if (activeFormats.specialFormats.codeBlock) {
            icon = codeBlock();
        } else if (activeFormats.specialFormats.spoiler) {
            icon = spoiler();
        }
        return icon;
    }
}
