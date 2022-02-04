/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useMemo } from "react";
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
export const AutoCompleteOption = React.forwardRef(function AutoCompleteOptionImpl(
    props: IAutoCompleteOptionProps,
    ref: React.Ref<HTMLLIElement>,
) {
    const { value, label = value, ...otherProps } = props;
    const { size, value: autoCompleteValue, multiple } = useContext(AutoCompleteContext);
    const classes = useMemo(() => autoCompleteClasses({ size }), [size]);
    const values = multiple && Array.isArray(autoCompleteValue) ? autoCompleteValue : [autoCompleteValue];
    const selected = values.indexOf(value) > -1;

    return (
        <ComboboxOption
            ref={ref}
            {...otherProps}
            className={cx(classes.option, props.className)}
            data-autocomplete-selected={selected || undefined}
            value={label}
        >
            <div className={classes.optionText}>
                <ComboboxOptionText />
                {props.data?.parentLabel && (
                    <span className={classes.parentLabel}>{` - ${props.data.parentLabel}`}</span>
                )}
            </div>
            {selected && (
                <span className={classes.checkmarkContainer}>
                    <Checkmark />
                </span>
            )}
        </ComboboxOption>
    );
});
