/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItem, { IMenuItemData } from "@rich-editor/components/toolbars/pieces/MenuItem";

interface IProps {
    menuItemData: IMenuItemData[];
    itemRole?: "menuitem" | "menuitemradio";
}

/**
 * A component that when used with MenuItem provides an accessible WCAG compliant Menu implementation.
 *
 * @see https://www.w3.org/TR/wai-aria-practices-1.1/#menu
 */
export default class MenuItems extends React.Component<IProps, {}> {
    public static defaultProps: Partial<IProps> = {
        itemRole: "menuitem",
    };

    private menuItemRefs: Array<MenuItem | null> = [];

    public render() {
        const { menuItemData } = this.props;
        const firstIndex = 0;
        const lastIndex = menuItemData.length - 1;
        return (
            <div className="richEditor-menu" role="menu">
                <div className="richEditor-menuItems MenuItems">
                    {this.props.menuItemData.map((itemData, index) => {
                        const prevIndex = index === firstIndex ? lastIndex : index - 1;
                        const nextIndex = index === lastIndex ? firstIndex : index + 1;
                        const focusPrevItem = () => {
                            const prevItem = this.menuItemRefs[prevIndex];
                            prevItem && prevItem.focus();
                        };
                        const focusNextItem = () => {
                            const nextItem = this.menuItemRefs[nextIndex];
                            nextItem && nextItem.focus();
                        };
                        return (
                            <MenuItem
                                {...itemData}
                                role={this.props.itemRole!}
                                key={index}
                                focusNextItem={focusNextItem}
                                focusPrevItem={focusPrevItem}
                                ref={ref => this.menuItemRefs.push(ref)}
                            />
                        );
                    })}
                </div>
            </div>
        );
    }

    // TODO focus handling

    /**
     * Focus the first menu item.
     */
    public focusFirstItem() {
        const firstItem = this.menuItemRefs[0];
        firstItem && firstItem.focus();
    }

    /**
     * Focus the last menu item.
     */
    public focusLastItem() {
        const lastIndex = this.menuItemRefs.length - 1;
        const lastItem = this.menuItemRefs[lastIndex];
        lastItem && lastItem.focus();
    }
}
