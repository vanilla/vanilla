/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import { t } from "@library/application";
import Formatter from "@rich-editor/quill/Formatter";
import { IFormats, RangeStatic } from "quill/core";
import { spoiler, codeBlock, blockquote, heading3, heading2, pilcrow } from "@library/components/icons/editorIcons";
import classNames from "classnames";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";

interface IProps {
    formatter: Formatter;
    activeFormats: IFormats;
    lastGoodSelection: RangeStatic;
    afterClickHandler?: () => void;
    menuRef?: React.RefObject<MenuItems>;
    renderAbove?: boolean;
    renderLeft?: boolean;
    onKeyDown?: (e) => any;
    className?: string;
}

export default class ParagraphToolbarMenuItems extends React.PureComponent<IProps> {
    public render() {
        return (
            <MenuItems
                itemRole="menuitemradio"
                ref={this.props.menuRef}
                menuItemData={this.menuItemData}
                renderAbove={this.props.renderAbove}
                renderLeft={this.props.renderLeft}
                onKeyDown={this.props.onKeyDown}
                className={classNames(this.props.className)}
            />
        );
    }

    private get menuItemData() {
        const { activeFormats } = this.props;
        let isParagraphEnabled = true;
        ["header", BlockquoteLineBlot.blotName, CodeBlockBlot.blotName, SpoilerLineBlot.blotName].forEach(item => {
            if (item in activeFormats) {
                isParagraphEnabled = false;
            }
        });

        const headerObjectLevel = typeof activeFormats.header === "object" ? activeFormats.header.level : null;

        return [
            {
                icon: pilcrow(),
                label: t("Format as Paragraph"),
                onClick: this.formatParagraph,
                isActive: isParagraphEnabled,
                isDisabled: isParagraphEnabled,
            },
            {
                icon: heading2(),
                label: t("Format as Title"),
                onClick: this.formatH2,
                isActive: activeFormats.header === 2 || headerObjectLevel === 2,
                isDisabled: activeFormats.header === 2 || headerObjectLevel === 2,
            },
            {
                icon: heading3(),
                label: t("Format as Subtitle"),
                onClick: this.formatH3,
                isActive: activeFormats.header === 3 || headerObjectLevel === 3,
                isDisabled: activeFormats.header === 3 || headerObjectLevel === 3,
            },
            {
                icon: blockquote(),
                label: t("Format as blockquote"),
                onClick: this.formatBlockquote,
                isActive: activeFormats[BlockquoteLineBlot.blotName] === true,
                isDisabled: activeFormats[BlockquoteLineBlot.blotName] === true,
            },
            {
                icon: codeBlock(),
                label: t("Format as code block"),
                onClick: this.formatCodeBlock,
                isActive: activeFormats[CodeBlockBlot.blotName] === true,
                isDisabled: activeFormats[CodeBlockBlot.blotName] === true,
            },
            {
                icon: spoiler("richEditorButton-icon"),
                label: t("Format as spoiler"),
                onClick: this.formatSpoiler,
                isActive: activeFormats[SpoilerLineBlot.blotName] === true,
                isDisabled: activeFormats[SpoilerLineBlot.blotName] === true,
            },
        ];
    }

    //
    // These are implicitly written out for performance reasons.
    // Lambas or binding in the render method slows down renders significantly.
    //

    private formatParagraph = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.paragraph(this.props.lastGoodSelection);
        this.props.afterClickHandler && this.props.afterClickHandler();
    };
    private formatH2 = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.h2(this.props.lastGoodSelection);
        this.props.afterClickHandler && this.props.afterClickHandler();
    };
    private formatH3 = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.h3(this.props.lastGoodSelection);
        this.props.afterClickHandler && this.props.afterClickHandler();
    };
    private formatBlockquote = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.blockquote(this.props.lastGoodSelection);
        this.props.afterClickHandler && this.props.afterClickHandler();
    };
    private formatCodeBlock = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.codeBlock(this.props.lastGoodSelection);
        this.props.afterClickHandler && this.props.afterClickHandler();
    };
    private formatSpoiler = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.spoiler(this.props.lastGoodSelection);
        this.props.afterClickHandler && this.props.afterClickHandler();
    };
}
