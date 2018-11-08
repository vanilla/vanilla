/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Menu, { MenuProps } from "react-select/lib/components/Menu";

/**
 * Overwrite for the menu component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props - menu props
 */
export default function menu(props: MenuProps<any>) {
    return <Menu {...props} className="suggestedTextInput-menu dropDown-contents" />;
}
