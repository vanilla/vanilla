/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useContext, useEffect, useMemo, useState } from "react";
import * as Reach from "@reach/combobox";
import * as Polymorphic from "../../polymorphic";
import "@reach/combobox/styles.css";
import { InputSize } from "../../types";
import { cx } from "@emotion/css";
import { autoCompleteClasses } from "./AutoComplete.styles";
import { inputClasses } from "../shared/input.styles";
import { DropDownArrow } from "../shared/DropDownArrow";
import { ClearIcon } from "../shared/ClearIcon";
import { AutoCompleteOption, IAutoCompleteOptionProps } from "./AutoCompleteOption";
import { AutoCompleteContext, IAutoCompleteContext, IAutoCompleteInputState } from "./AutoCompleteContext";
import { AutoCompleteLookupOptions } from "./AutoCompleteLookupOptions";

function AutoCompleteArrow() {
    const { size } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    return (
        <div className={classes.autoCompleteArrow}>
            <DropDownArrow />
        </div>
    );
}

function AutoCompleteClear(props: { onClear(): void }) {
    const { onClear } = props;
    const { size } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    return (
        <div
            className={classes.autoCompleteClear}
            tabIndex={0}
            role="button"
            onClick={onClear}
            onKeyDown={(event) => {
                switch (event.key) {
                    case " ":
                    case "Enter":
                        onClear();
                        break;
                }
            }}
        >
            <ClearIcon style={{ color: "#777a80" }} />
        </div>
    );
}

export interface IAutoCompleteOptionState {
    options: IAutoCompleteOptionProps[];
    optionByValue: { [value: string]: IAutoCompleteOptionProps };
    optionByLabel: { [label: string]: IAutoCompleteOptionProps };
    optionProvider?: React.ReactNode;
}

function makeOptionState(
    options: IAutoCompleteOptionProps[],
    optionProvider?: React.ReactNode,
): IAutoCompleteOptionState {
    return {
        options,
        optionByValue: options.reduce(
            (acc, option) => ({
                ...acc,
                [option.value]: option,
            }),
            {},
        ),
        optionByLabel: options.reduce(
            (acc, option) => ({
                ...acc,
                [option.label ?? option.value]: option,
            }),
            {},
        ),
        optionProvider,
    };
}

const OPTION_TYPES: any[] = [AutoCompleteOption];
const OPTION_PROVIDER_TYPES: any[] = [AutoCompleteLookupOptions];

export interface IAutoCompleteProps {
    value?: any;
    className?: string;
    inputClassName?: string;
    popoverClassName?: string;
    size?: InputSize;
    multiple?: boolean;
    disabled?: boolean;
    autoFocus?: boolean;
    clear?: boolean;
    children?: React.ReactNode | React.ReactNode[];
    onChange?(value: any): void;
    onSearch?(value: string): void;
}

/**
 * An AutoComplete renders a searchable dropdown.
 * It expects as children a `AutoCompleteInput` and a `AutoCompletePopover`.
 * We can customize the size of the autocomplete with the `size` property.
 * All of input's props are available and will be forwarded to ComboBoxInput.
 * See ReachUI's Combobox: https://reach.tech/combobox
 */
export const AutoComplete = React.forwardRef(function AutoCompleteImpl(props, forwardedRef) {
    const { value, clear, disabled, autoFocus, size, children, onChange, onSearch, ...otherProps } = props;
    const classes = autoCompleteClasses({ size });
    const classesInput = inputClasses({ size });
    const [controlledOptions, setControlledOptions] = useState<IAutoCompleteOptionProps[]>();

    /**
     * Makes optimized arrays of options from either the children or controlled options passed through the context.
     */
    const { options, optionByValue, optionByLabel, optionProvider } = useMemo<IAutoCompleteOptionState>(() => {
        if (controlledOptions) {
            return makeOptionState(controlledOptions);
        }
        const options: IAutoCompleteOptionProps[] = [];
        let optionProvider: React.ReactNode;
        React.Children.forEach(children, (child: React.ReactElement<IAutoCompleteOptionProps>) => {
            if (child && OPTION_TYPES.includes(child.type)) {
                options.push(child.props);
            } else if (child && OPTION_PROVIDER_TYPES.includes(child.type)) {
                optionProvider = child;
            } else if (child) {
                throw new Error(`${child.type["name"]} is not supported by AutoComplete`);
            }
        });
        return makeOptionState(options, optionProvider);
    }, [children, controlledOptions]);

    // We need to control the value to be able to clear it.
    const displayValue = (optionByValue && optionByValue[value]?.label) ?? String(value ?? "");
    const [state, setState] = useState<IAutoCompleteInputState>({
        status: "initial",
        value: displayValue,
    });

    /**
     * Filters options using the search string and returns them.
     */
    const filteredOptions = useMemo<IAutoCompleteOptionProps[]>(() => {
        if (state.status !== "suggesting") {
            return options;
        }
        const inputValue = String(state.value ?? "");
        const lowerCaseSearch = inputValue.trim().toLowerCase();
        const terms = lowerCaseSearch.split(/[ +]/);
        const matchedOptions = (options ?? []).map((option) => {
            const label = option.label ?? String(option.value ?? "");
            const lowerCaseLabel = label.toLowerCase();
            return { option, matches: terms.filter((term) => lowerCaseLabel.includes(term)).length };
        });
        return matchedOptions
            .filter(({ option, matches }) => matches > 0)
            .sort((a, b) => b.matches - a.matches)
            .map(({ option }) => option);
    }, [state, options]);

    /**
     * When the controlled value changes, set the input value.
     */
    useEffect(() => {
        if (displayValue) {
            setState({ status: "selected", value: displayValue });
        } else {
            setState({ status: "initial", value: "" });
        }
    }, [displayValue]);

    /**
     * Empty the input and call onChange with undefined value.
     */
    const onClear = useCallback(() => {
        onChange && onChange(undefined);
    }, [onChange]);

    /**
     * Select a label and send it's value through onChange.
     */
    const onSelect = useCallback(
        (label: string) => {
            const value = optionByLabel[label].value;
            onChange && onChange(value);
        },
        [onChange, optionByLabel],
    );

    /**
     * Handle a change in the input, suggesting options.
     */
    const onInputChange = useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) => {
            setState({ status: "suggesting", value: event.target.value });
            onSearch && onSearch(event.target.value);
        },
        [onSearch],
    );

    /**
     * Handles closing the popover, clearing the query.
     */
    const onInputBlur = useCallback(
        (event: React.FocusEvent<HTMLInputElement>) => {
            if (displayValue) {
                setState({ status: "selected", value: displayValue });
            } else {
                setState({ status: "initial", value: "" });
            }
        },
        [displayValue],
    );

    /**
     * Provides a context for the children.
     */
    const context = useMemo<IAutoCompleteContext>(
        () => ({
            onClear,
            inputState: state,
            setInputState: setState,
            value,
            size,
            setOptions: setControlledOptions,
        }),
        [state, onClear, value, size],
    );

    return (
        <AutoCompleteContext.Provider value={context}>
            <Reach.Combobox onSelect={onSelect} ref={forwardedRef} openOnFocus>
                <div className={classes.inputContainer}>
                    <Reach.ComboboxInput
                        {...otherProps}
                        disabled={disabled}
                        autoFocus={autoFocus}
                        onChange={onInputChange}
                        onBlur={onInputBlur}
                        value={String(state.value)}
                        className={cx(classesInput.input, classes.input, props.inputClassName)}
                    />
                    <div className={classes.inputActions}>
                        <AutoCompleteArrow />
                        {clear && value && <AutoCompleteClear onClear={onClear!} />}
                    </div>
                </div>
                <Reach.ComboboxPopover
                    className={cx(classes.popover, props.popoverClassName)}
                    data-autocomplete-state={state.status}
                >
                    <Reach.ComboboxList>
                        {filteredOptions.map((props) => (
                            <AutoCompleteOption
                                key={props.value}
                                {...props}
                                onMouseDown={() => {
                                    // This is a workaround for some javascript intercepting onClick for options.
                                    // Without this the Add Pocket page would not be able to select options.
                                    onSelect(props.label ?? props.value);
                                }}
                            />
                        ))}
                    </Reach.ComboboxList>
                </Reach.ComboboxPopover>
            </Reach.Combobox>
            {optionProvider}
        </AutoCompleteContext.Provider>
    );
}) as Polymorphic.ForwardRefComponent<"input", IAutoCompleteProps>;
