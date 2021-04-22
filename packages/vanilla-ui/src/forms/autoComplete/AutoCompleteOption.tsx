/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { cx } from "@emotion/css";
import { ComboboxOption } from "@reach/combobox";
import { autoCompleteClasses } from "./AutoComplete.styles";
import { AutoCompleteContext } from "./AutoComplete";

export interface IAutoCompleteOptionProps extends React.ComponentProps<typeof ComboboxOption> {}

/**
 * Renders a list element and provides a value for the searchable dropdown.
 * See ReachUI's ComboboxOption: https://reach.tech/combobox#comboboxoption
 */
export const AutoCompleteOption = React.forwardRef(function AutoCompleteOptionImpl(
    props: IAutoCompleteOptionProps,
    forwardedRef: React.Ref<HTMLLIElement>,
) {
    const { size } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    return (
        <ComboboxOption {...props} className={cx(classes.option, props.className)} ref={forwardedRef}>
            {props.children}
        </ComboboxOption>
    );
});
