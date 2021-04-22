/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { ComboboxList } from "@reach/combobox";

export interface IAutoCompleteListProps extends React.ComponentProps<typeof ComboboxList> {}

/**
 * Contains AutoCompleteOptions and sets up proper aria attributes for the list.
 * See ReachUI's ComboboxList: https://reach.tech/combobox#comboboxlist
 */
export const AutoCompleteList = React.forwardRef(function AutoCompleteListImpl(
    props: IAutoCompleteListProps,
    forwardedRef: React.Ref<HTMLUListElement>,
) {
    return (
        <ComboboxList {...props} ref={forwardedRef}>
            {props.children}
        </ComboboxList>
    );
});
