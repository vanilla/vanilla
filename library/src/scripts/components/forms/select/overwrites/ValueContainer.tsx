/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";

export default function ValueContainer({ children, ...props }: any) {
    return (
        <components.ValueContainer
            {...props}
            className="bigInput-valueContainer inputBlock-inputText InputBox inputText isLarge"
        >
            {children}
        </components.ValueContainer>
    );
}
