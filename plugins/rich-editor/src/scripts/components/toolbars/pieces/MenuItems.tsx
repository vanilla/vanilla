/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItem, { IMenuItemData } from "@rich-editor/components/toolbars/pieces/MenuItem";

interface IProps {
    itemData: IMenuItemData[];
}

export default class MenuItems extends React.Component<IProps, {}> {
    public render() {
        const items = this.props.itemData.map((item, key) => {
            return (
                <MenuItem
                    key={key}
                    label={item.label}
                    icon={item.icon}
                    isActive={item.isEnabled()}
                    disabled={false}
                    onClick={item.formatter}
                />
            );
        });

        return (
            <div className="richEditor-menu" role="menu">
                <div className="richEditor-menuItems MenuItems">{items}</div>
            </div>
        );
    }

    // TODO focus handling

    /**
     * Be sure to strip out all other formats before formatting as code.
     */
    // private codeFormatter = (menuItemData: IMenuItemData) => {
    //     if (!this.props.currentSelection) {
    //         return;
    //     }
    //     this.quill.removeFormat(
    //         this.props.currentSelection.index,
    //         this.props.currentSelection.length,
    //         Quill.sources.API,
    //     );
    //     this.quill.formatText(
    //         this.props.currentSelection.index,
    //         this.props.currentSelection.length,
    //         "code-inline",
    //         !menuItemData.active,
    //         Quill.sources.USER,
    //     );
    // };
}
