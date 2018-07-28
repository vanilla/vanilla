/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItem from "@rich-editor/components/toolbars/pieces/MenuItem";

interface IProps {
    children: (firstItemRef: React.RefObject<MenuItem>, lastItemRef: React.RefObject<MenuItem>) => JSX.Element;
}

export default class MenuItems extends React.Component<IProps, {}> {
    private firstItemRef: React.RefObject<MenuItem> = React.createRef();
    private lastItemRef: React.RefObject<MenuItem> = React.createRef();

    public render() {
        return (
            <div className="richEditor-menu" role="menu">
                <div className="richEditor-menuItems MenuItems">
                    {this.props.children(this.firstItemRef, this.lastItemRef)}
                </div>
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
