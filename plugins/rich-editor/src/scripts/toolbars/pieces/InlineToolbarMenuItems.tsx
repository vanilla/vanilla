/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { IFormats, RangeStatic } from "quill/core";
import classNames from "classnames";
import { BoldIcon, ItalicIcon, LinkIcon, StrikeIcon, CodeIcon } from "@library/icons/editorIcons";
import MenuItems from "@library/editor/toolbars/pieces/MenuItems";
import { IMenuItemData } from "@library/editor/toolbars/pieces/MenuItem";
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
                icon: <BoldIcon />,
                isActive: activeFormats.bold === true,
                onClick: this.formatBold,
                onlyIcon: true,
            },
            {
                label: t("Format as Italic"),
                icon: <ItalicIcon />,
                isActive: activeFormats.italic === true,
                onClick: this.formatItalic,
                onlyIcon: true,
            },
            {
                label: t("Format as Strikethrough"),
                icon: <StrikeIcon />,
                isActive: activeFormats.strike === true,
                onClick: this.formatStrike,
                onlyIcon: true,
            },
            {
                label: t("Format as Inline Code"),
                icon: <CodeIcon />,
                isActive: activeFormats.code === true,
                onClick: this.formatCode,
                onlyIcon: true,
            },
            {
                label: t("Format as Link"),
                icon: <LinkIcon />,
                isActive: typeof activeFormats.link === "string",
                onClick: onLinkClick,
                onlyIcon: true,
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
