/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";

/**
 * Overwrite for the valueContainer component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param children
 * @param props
 */
export default function valueContainer({ children, ...props }: any) {
    return (
        <components.ValueContainer
            {...props}
            className="suggestedTextInput-valueContainer inputBlock-inputText inputText tokensContainer"
        >
            {children}
        </components.ValueContainer>
    );
}
