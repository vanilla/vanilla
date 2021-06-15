/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useEffect } from "react";
import { cx } from "@emotion/css";
import { ComboboxOption, ComboboxOptionText } from "@reach/combobox";
import { autoCompleteClasses } from "./AutoComplete.styles";
import { AutoCompleteContext } from "./AutoCompleteContext";
import { Checkmark } from "../shared/Checkmark";

export interface IAutoCompleteOption {
    value: any;
    label?: string;
    data?: any;
}

export interface IAutoCompleteOptionProps
    extends IAutoCompleteOption,
        Omit<React.ComponentProps<typeof ComboboxOption>, "as" | "value"> {}

/**
 * Renders a list element and provides a value for the searchable dropdown.
 * See ReachUI's ComboboxOption: https://reach.tech/combobox#comboboxoption
 */
export const AutoCompleteOption = React.forwardRef(function AutoCompleteOptionImpl(props: IAutoCompleteOptionProps) {
    const { value, label = value, ...otherProps } = props;
    const { size, value: autoCompleteValue } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    const selected = value == autoCompleteValue;

    return (
        <ComboboxOption
            {...otherProps}
            className={cx(classes.option, props.className)}
            data-autocomplete-selected={selected || undefined}
            value={label}
        >
            <div className={classes.optionText}>
                <ComboboxOptionText />
            </div>
            {selected && <Checkmark />}
        </ComboboxOption>
    );
});
