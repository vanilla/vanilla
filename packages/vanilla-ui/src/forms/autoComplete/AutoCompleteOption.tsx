/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useMemo } from "react";
import { cx } from "@emotion/css";
import { ComboboxOption, ComboboxOptionText } from "@reach/combobox";
import { autoCompleteClasses } from "./AutoComplete.styles";
import { useAutoCompleteContext } from "./AutoCompleteContext";
import { Checkmark } from "../shared/Checkmark";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";

export interface IAutoCompleteOption {
    value: any;
    label?: string;
    extraLabel?: string;
    data?: any;
    group?: string;
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
    const { value, label = value, extraLabel, ...otherProps } = props;
    const { size, value: autoCompleteValue, multiple } = useAutoCompleteContext();
    const classes = useMemo(() => autoCompleteClasses({ size }), [size]);
    const values = multiple && Array.isArray(autoCompleteValue) ? autoCompleteValue : [autoCompleteValue];
    const selected = values.indexOf(value) > -1;

    const extraLabelContent = props.data?.parentLabel ?? extraLabel;

    const icon = props.data?.icon;

    return (
        <ComboboxOption
            ref={ref}
            {...otherProps}
            className={cx(classes.option, props.className)}
            data-autocomplete-selected={selected || undefined}
            value={label}
        >
            <div className={cx(classes.optionText, icon && classes.iconLayout)}>
                {icon && <UserPhoto size={UserPhotoSize.XSMALL} userInfo={icon ?? deletedUserFragment()} />}
                <span>
                    <ComboboxOptionText />
                    {extraLabelContent && <span className={classes.parentLabel}>{` - ${extraLabelContent}`}</span>}
                    {props.data?.labelSuffix}
                </span>
            </div>
            {selected && (
                <span className={classes.checkmarkContainer}>
                    <Checkmark />
                </span>
            )}
        </ComboboxOption>
    );
});
