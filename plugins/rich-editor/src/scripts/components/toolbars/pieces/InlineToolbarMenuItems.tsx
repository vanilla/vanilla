/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import MenuItem from "@rich-editor/components/toolbars/pieces/MenuItem";
import { t } from "@dashboard/application";
import * as icons from "../../icons";

import Formatter from "@rich-editor/quill/Formatter";
import { IFormats } from "quill/core";

interface IProps {
    formatter: Formatter;
    activeFormats: IFormats;
    onLinkClick: (event: React.MouseEvent<HTMLButtonElement>) => void;
}

export default class InlineToolbarMenuItems extends React.Component<IProps> {
    public render() {
        const { formatter, activeFormats, onLinkClick } = this.props;

        return (
            <MenuItems>
                {(firstItemRef, lastItemRef) => (
                    <React.Fragment>
                        <MenuItem
                            ref={firstItemRef}
                            label={t("Format as Bold")}
                            icon={icons.bold()}
                            isActive={activeFormats.bold === true}
                            onClick={formatter.bold}
                        />
                        <MenuItem
                            label={t("Format as Italic")}
                            icon={icons.italic()}
                            isActive={activeFormats.italic === true}
                            onClick={formatter.italic}
                        />
                        <MenuItem
                            label={t("Format as Strikethrough")}
                            icon={icons.strike()}
                            isActive={activeFormats.strike === true}
                            onClick={formatter.strike}
                        />
                        <MenuItem
                            label={t("Format as Inline Code")}
                            icon={icons.code()}
                            isActive={activeFormats["code-inline"] === true}
                            onClick={formatter.codeInline}
                        />
                        <MenuItem
                            ref={lastItemRef}
                            label={t("Format as Link")}
                            icon={icons.link()}
                            isActive={typeof activeFormats.link === "string"}
                            onClick={onLinkClick}
                        />
                    </React.Fragment>
                )}
            </MenuItems>
        );
    }
}
