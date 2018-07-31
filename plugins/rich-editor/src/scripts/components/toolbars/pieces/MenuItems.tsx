/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItem from "@rich-editor/components/toolbars/pieces/MenuItem";

interface IProps {
    children: (menuItemRefs: Array<React.RefObject<MenuItem>>) => JSX.Element;
}

export default class MenuItems extends React.Component<IProps, {}> {
    private menuItemRefs: Array<React.RefObject<MenuItem>> = [];

    public render() {
        console.log(this.menuItemRefs);
        return (
            <div className="richEditor-menu" role="menu">
                <div className="richEditor-menuItems MenuItems">{this.props.children(this.menuItemRefs)}</div>
            </div>
        );
    }

    // TODO focus handling

    public focusFirstButton() {}

    public focusLastButton() {}
}
