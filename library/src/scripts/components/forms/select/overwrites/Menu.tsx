/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";

export default function Menu(props: any) {
    return (
        <React.Fragment>
            <components.Menu {...props} className="suggestedTextInput-menu dropDown-contents">
                {props.children}
            </components.Menu>
        </React.Fragment>
    );
}
