/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IFormats } from "quill/core";
import HeadingBlot from "quill/formats/header";
import {
    Heading2Icon,
    Heading3Icon,
    Heading4Icon,
    PilcrowIcon,
    Heading5Icon,
    ListOrderedIcon,
    ListUnorderedIcon,
    CodeBlockIcon,
    SpoilerIcon,
    BlockquoteIcon,
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
        let icon = <PilcrowIcon />;
        if (activeFormats.headings.heading2) {
            icon = <Heading2Icon />;
        } else if (activeFormats.headings.heading3) {
            icon = <Heading3Icon />;
        } else if (activeFormats.headings.heading4) {
            icon = <Heading4Icon />;
        } else if (activeFormats.headings.heading5) {
            icon = <Heading5Icon />;
        } else if (activeFormats.lists.ordered) {
            icon = <ListOrderedIcon />;
        } else if (activeFormats.lists.unordered) {
            icon = <ListUnorderedIcon />;
        } else if (activeFormats.specialFormats.blockQuote) {
            icon = <BlockquoteIcon />;
        } else if (activeFormats.specialFormats.codeBlock) {
            icon = <CodeBlockIcon />;
        } else if (activeFormats.specialFormats.spoiler) {
            icon = <SpoilerIcon />;
        }
        return icon;
    }
}
