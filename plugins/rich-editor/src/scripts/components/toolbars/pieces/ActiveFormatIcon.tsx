/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IFormats } from "quill/core";
import HeadingBlot from "quill/formats/header";
import { heading2, heading3, blockquote, codeBlock, spoiler, pilcrow } from "@library/components/icons/editorIcons";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";

interface IProps {
    activeFormats: IFormats;
}

export default class ActiveFormatIcon extends React.Component<IProps, {}> {
    public render() {
        const { activeFormats } = this.props;
        const headingFormat = activeFormats[HeadingBlot.blotName];
        if (typeof headingFormat === "object") {
            if (headingFormat.level === 2) {
                return heading2();
            }
            if (headingFormat.level === 3) {
                return heading3();
            }
        }
        if (headingFormat === 2) {
            return heading2();
        }
        if (headingFormat === 3) {
            return heading3();
        }
        if (activeFormats[BlockquoteLineBlot.blotName] === true) {
            return blockquote();
        }
        if (activeFormats[CodeBlockBlot.blotName] === true) {
            return codeBlock();
        }
        if (activeFormats[SpoilerLineBlot.blotName] === true) {
            return spoiler("richEditorButton-icon");
        }

        // Fallback to paragraph formatting.
        return pilcrow();
    }
}
