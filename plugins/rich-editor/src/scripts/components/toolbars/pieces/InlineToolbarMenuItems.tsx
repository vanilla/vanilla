/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import { IMenuItemData } from "@rich-editor/components/toolbars/pieces/MenuItem";
import { t } from "@library/application";
import * as icons from "@library/components/icons/editorIcons";
import Formatter from "@rich-editor/quill/Formatter";
import { IFormats, RangeStatic } from "quill/core";
import classNames from "classnames";

interface IProps {
    formatter: Formatter;
    activeFormats: IFormats;
    lastGoodSelection: RangeStatic;
    onLinkClick: (event: React.MouseEvent<HTMLButtonElement>) => void;
    className?: string;
}

export default class InlineToolbarMenuItems extends React.PureComponent<IProps> {
    public render() {
        return (
            <MenuItems
                menuItemData={this.menuItemData}
                className={classNames("richEditor-inlineToolbarContainer", this.props.className)}
            />
        );
    }

    private get menuItemData(): IMenuItemData[] {
        const { activeFormats, onLinkClick } = this.props;

        return [
            {
                label: t("Format as Bold"),
                icon: icons.bold(),
                isActive: activeFormats.bold === true,
                onClick: this.formatBold,
            },
            {
                label: t("Format as Italic"),
                icon: icons.italic(),
                isActive: activeFormats.italic === true,
                onClick: this.formatItalic,
            },
            {
                label: t("Format as Strikethrough"),
                icon: icons.strike(),
                isActive: activeFormats.strike === true,
                onClick: this.formatStrike,
            },
            {
                label: t("Format as Inline Code"),
                icon: icons.code(),
                isActive: activeFormats.code === true,
                onClick: this.formatCode,
            },
            {
                label: t("Format as Link"),
                icon: icons.link(),
                isActive: typeof activeFormats.link === "string",
                onClick: onLinkClick,
            },
        ];
    }

    //
    // These are implicitly written out for performance reasons.
    // Lambas or binding in the render method slows down renders significantly.
    //

    private formatBold = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.bold(this.props.lastGoodSelection);
    };
    private formatItalic = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.italic(this.props.lastGoodSelection);
    };
    private formatStrike = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.strike(this.props.lastGoodSelection);
    };
    private formatCode = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.codeInline(this.props.lastGoodSelection);
    };
}
