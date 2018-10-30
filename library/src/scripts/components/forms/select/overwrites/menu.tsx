/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";

/**
 * Overwrite for the menu component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props - menu props
 */
export default function menu(props: any) {
    return (
        <React.Fragment>
            <components.Menu {...props} className="suggestedTextInput-menu dropDown-contents">
                {props.children}
            </components.Menu>
        </React.Fragment>
    );
}
