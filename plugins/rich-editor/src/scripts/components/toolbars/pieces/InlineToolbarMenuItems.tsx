/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import MenuItem, { IMenuItemData } from "@rich-editor/components/toolbars/pieces/MenuItem";
import { t } from "@dashboard/application";
import * as icons from "../../icons";

import Formatter from "@rich-editor/quill/Formatter";
import { IFormats, RangeStatic } from "quill/core";

interface IProps {
    formatter: Formatter;
    activeFormats: IFormats;
    lastGoodSelection: RangeStatic;
    onLinkClick: (event: React.MouseEvent<HTMLButtonElement>) => void;
}

export default class InlineToolbarMenuItems extends React.Component<IProps> {
    public render() {
        return <MenuItems menuItemData={this.menuItemData} />;
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
                isActive: activeFormats["code-inline"] === true,
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

    private formatBold = () => {
        this.props.formatter.bold(this.props.lastGoodSelection);
    };
    private formatItalic = () => {
        this.props.formatter.italic(this.props.lastGoodSelection);
    };
    private formatStrike = () => {
        this.props.formatter.strike(this.props.lastGoodSelection);
    };
    private formatCode = () => {
        this.props.formatter.codeInline(this.props.lastGoodSelection);
    };
}
