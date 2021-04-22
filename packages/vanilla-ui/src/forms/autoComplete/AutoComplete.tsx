/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import * as Reach from "@reach/combobox";
import "@reach/combobox/styles.css";
import { InputSize } from "../../types";

interface IAutoCompleteContext {
    size?: InputSize;
    onClear?(): void;
    setValue?(value: string): void;
    value?: string;
}

/** @internal */
export const AutoCompleteContext = React.createContext<IAutoCompleteContext>({});

export interface IAutoCompleteProps extends React.ComponentProps<typeof Reach.Combobox> {
    size?: InputSize;
    onClear?(): void;
}

/**
 * An AutoComplete renders a searchable dropdown.
 * It expects as children a `AutoCompleteInput` and a `AutoCompletePopover`.
 * We can customize the size of the autocomplete with the `size` property.
 * See ReachUI's Combobox: https://reach.tech/combobox
 */
export const AutoComplete = React.forwardRef(function AutoCompleteImpl(
    props: IAutoCompleteProps,
    forwardedRef: React.Ref<HTMLDivElement>,
) {
    const { size, ...otherProps } = props;
    // We need to control the value to be able to clear it.
    const [controlledValue, setControlledValue] = useState<string | undefined>();
    const onClear = () => {
        setControlledValue("");
        props.onClear && props.onClear();
    };
    const onSelect = (value: string) => {
        setControlledValue(value);
        props.onSelect && props.onSelect(value);
    };
    return (
        <AutoCompleteContext.Provider value={{ onClear, value: controlledValue, setValue: setControlledValue, size }}>
            <Reach.Combobox {...otherProps} onSelect={onSelect} ref={forwardedRef}>
                {props.children}
            </Reach.Combobox>
        </AutoCompleteContext.Provider>
    );
});
