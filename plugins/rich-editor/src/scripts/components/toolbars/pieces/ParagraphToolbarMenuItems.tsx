/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import { t } from "@dashboard/application";
import * as icons from "../../icons";
import Formatter from "@rich-editor/quill/Formatter";
import { IFormats, RangeStatic } from "quill/core";

interface IProps {
    formatter: Formatter;
    activeFormats: IFormats;
    lastGoodSelection: RangeStatic;
    menuRef?: React.RefObject<MenuItems>;
}

export default class ParagraphToolbarMenuItems extends React.PureComponent<IProps> {
    public render() {
        return <MenuItems itemRole={"menuitemradio"} ref={this.props.menuRef} menuItemData={this.menuItemData} />;
    }

    private get menuItemData() {
        const { activeFormats } = this.props;
        let isParagraphEnabled = true;
        ["header", "blockquote-line", "codeBlock", "spoiler-line"].forEach(item => {
            if (item in activeFormats) {
                isParagraphEnabled = false;
            }
        });
        return [
            {
                icon: icons.pilcrow(),
                label: t("Format as Paragraph"),
                onClick: this.formatParagraph,
                isActive: isParagraphEnabled,
            },
            {
                icon: icons.heading2(),
                label: t("Format as Title"),
                onClick: this.formatH2,
                isActive: activeFormats.header === 2,
            },
            {
                icon: icons.heading3(),
                label: t("Format as Subtitle"),
                onClick: this.formatH3,
                isActive: activeFormats.header === 3,
            },
            {
                icon: icons.blockquote(),
                label: t("Format as blockquote"),
                onClick: this.formatBlockquote,
                isActive: activeFormats["blockquote-line"] === true,
            },
            {
                icon: icons.codeBlock(),
                label: t("Format as code block"),
                onClick: this.formatCodeBlock,
                isActive: activeFormats.codeBlock === true,
            },
            {
                icon: icons.spoiler(),
                label: t("Format as spoiler"),
                onClick: this.formatSpoiler,
                isActive: activeFormats["spoiler-line"] === true,
            },
        ];
    }

    //
    // These are implicitly written out for performance reasons.
    // Lambas or binding in the render method slows down renders significantly.
    //

    private formatParagraph = () => {
        this.props.formatter.paragraph(this.props.lastGoodSelection);
    };
    private formatH2 = () => {
        this.props.formatter.h2(this.props.lastGoodSelection);
    };
    private formatH3 = () => {
        this.props.formatter.h3(this.props.lastGoodSelection);
    };
    private formatBlockquote = () => {
        this.props.formatter.blockquote(this.props.lastGoodSelection);
    };
    private formatCodeBlock = () => {
        this.props.formatter.codeBlock(this.props.lastGoodSelection);
    };
    private formatSpoiler = () => {
        this.props.formatter.spoiler(this.props.lastGoodSelection);
    };
}
