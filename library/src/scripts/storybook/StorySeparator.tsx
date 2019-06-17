/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, paddings, singleBorder, unit } from "@library/styles/styleHelpers";

/**
 * Separator, for react storybook.
 */
export function StorySeparator(props) {
    const globalVars = globalVariables();
    return (
        <hr
            style={{
                display: "block",
                margin: "auto",
                borderBottom: singleBorder(),
                ...margins({
                    vertical: globalVars.spacer.size,
                }),
                maxWidth: "100%",
                width: props.width ? unit(props.width) : undefined,
            }}
        />
    );
}
