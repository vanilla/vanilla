/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import * as icons from "@library/icons/editorIcons";
import { IFormats, RangeStatic } from "quill/core";
import classNames from "classnames";
import { inlineToolbarClasses } from "@rich-editor/toolbars/inlineToolbarClasses";
import { IMenuItemData } from "@rich-editor/toolbars/pieces/MenuItem";
import MenuItems from "@rich-editor/toolbars/pieces/MenuItems";
import Formatter from "@rich-editor/quill/Formatter";

interface IProps {
    formatter: Formatter;
    activeFormats: IFormats;
    lastGoodSelection: RangeStatic;
    onLinkClick: (event: React.MouseEvent<HTMLButtonElement>) => void;
    className?: string;
    legacyMode?: boolean;
}

export default class InlineToolbarMenuItems extends React.PureComponent<IProps> {
    public render() {
        const classesInlineToolbar = inlineToolbarClasses(!!this.props.legacyMode);
        return (
            <MenuItems
                menuItemData={this.menuItemData}
                className={classNames(
                    "richEditor-inlineToolbarContainer",
                    this.props.className,
                    classesInlineToolbar.root,
                )}
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
        this.props.formatter.bold();
    };
    private formatItalic = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.italic();
    };
    private formatStrike = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.strike();
    };
    private formatCode = (event: React.MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.formatter.codeInline();
    };
}
