/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { cx } from "@emotion/css";
import { ComboboxPopover } from "@reach/combobox";
import { autoCompleteClasses } from "./AutoComplete.styles";
import { AutoCompleteContext } from "./AutoComplete";

export interface IAutoCompletePopoverProps extends React.ComponentProps<typeof ComboboxPopover> {}

/**
 * Contains the popup that renders the list. Because some UI needs to render more than the list in the popup,
 * you need to render one of these around the list. For example, maybe you want to render the number of results suggested.
 * See ReachUI's ComboboxOptionText: https://reach.tech/combobox#comboboxoption
 */
export const AutoCompletePopover = React.forwardRef(function AutoCompletePopoverImpl(
    props: IAutoCompletePopoverProps,
    forwardedRef: React.Ref<HTMLDivElement>,
) {
    const { size } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    return (
        <ComboboxPopover {...props} className={cx(classes.popover, props.className)} ref={forwardedRef}>
            {props.children}
        </ComboboxPopover>
    );
});
