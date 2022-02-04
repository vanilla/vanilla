/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useContext, useEffect, useMemo, useRef, useState } from "react";
import * as Reach from "@reach/combobox";
import * as Polymorphic from "../../polymorphic";
import "@reach/combobox/styles.css";
import { InputSize } from "../../types";
import { cx } from "@emotion/css";
import { autoCompleteClasses } from "./AutoComplete.styles";
import { inputClasses } from "../shared/input.styles";
import { DropDownArrow } from "../shared/DropDownArrow";
import { ClearIcon } from "../shared/ClearIcon";
import { CloseIcon } from "../shared/CloseIcon";
import { AutoCompleteOption, IAutoCompleteOption, IAutoCompleteOptionProps } from "./AutoCompleteOption";
import { AutoCompleteContext, IAutoCompleteContext, IAutoCompleteInputState } from "./AutoCompleteContext";
import { AutoCompleteLookupOptions } from "./AutoCompleteLookupOptions";
import { useMeasure } from "@vanilla/react-utils/src";

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

function AutoCompleteToken(props: { label: string; onUnSelect(): void }) {
    const { onUnSelect, label } = props;
    const { size } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    return (
        <div className={cx("autocomplete-token", classes.inputTokenTag)} tabIndex={0}>
            <label>{label}</label>
            <div
                className={cx(classes.autoCompleteClear)}
                tabIndex={0}
                role="button"
                onClick={onUnSelect}
                onKeyDown={(event) => {
                    switch (event.key) {
                        case " ":
                        case "Enter":
                            onUnSelect();
                            break;
                    }
                }}
            >
                <button>
                    <CloseIcon className={cx(classes.autoCompleteClose)} style={{ color: "#777a80" }} />
                </button>
            </div>
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

export interface IAutoCompleteProps {
    options?: IAutoCompleteOption[];
    optionProvider?: React.ReactNode;
    value?: any | any[];
    className?: string;
    inputClassName?: string;
    popoverClassName?: string;
    size?: InputSize;
    multiple?: boolean;
    disabled?: boolean;
    autoFocus?: boolean;
    clear?: boolean;
    onChange?(value: any | any[]): void;
    onSearch?(value: string): void;
    allowArbitraryInput?: boolean;
}

/**
 * An AutoComplete renders a searchable dropdown.
 * It expects as children a `AutoCompleteInput` and a `AutoCompletePopover`.
 * We can customize the size of the autocomplete with the `size` property.
 * All of input's props are available and will be forwarded to ComboBoxInput.
 * See ReachUI's Combobox: https://reach.tech/combobox
 */
export const AutoComplete = React.forwardRef(function AutoCompleteImpl(props, forwardedRef) {
    const {
        value,
        clear,
        disabled,
        autoFocus,
        size,
        onChange,
        onSearch,
        inputClassName,
        allowArbitraryInput,
        placeholder,
        optionProvider,
        ...otherProps
    } = props;
    const classes = autoCompleteClasses({ size });
    const classesInput = inputClasses({ size, useInputRowSize: true });
    const [controlledOptions, setControlledOptions] = useState<IAutoCompleteOptionProps[]>();
    const [arbitraryValues, setArbitraryValues] = useState<string[]>([]);

    const { options, optionByValue, optionByLabel } = useMemo(
        () => makeOptionState(controlledOptions ? controlledOptions : props.options ?? [], optionProvider),
        [controlledOptions, optionProvider, props.options],
    );

    const values = value;
    const isMultiple = props.multiple || Array.isArray(value);
    // We need to control the value to be able to clear it.
    const displayValue = isMultiple
        ? ""
        : (optionByValue && optionByValue[value]?.label) ?? (value ? String(value) : undefined);
    const [state, setState] = useState<IAutoCompleteInputState>({
        status: "initial",
        value: displayValue,
    });
    const [valuesState, setValuesState] = useState(value);

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
        } else if (Array.isArray(values)) {
            setState({ status: "selected", value: "" });
            setValuesState(values);
        } else {
            setState({ status: "initial", value: "" });
        }
    }, [displayValue, values]);

    /**
     * When arbitrary values are allowed, add them to the controlled options list
     */
    useEffect(() => {
        if (allowArbitraryInput && state.value) {
            setControlledOptions([
                {
                    value: state.value,
                },
            ]);
        }
    }, [state.value, allowArbitraryInput]);

    /**
     * Empty the input and call onChange with undefined value.
     */
    const onClear = useCallback(() => {
        if (isMultiple) {
            setValuesState([]);
        }
        if (allowArbitraryInput) {
            setArbitraryValues([]);
        }
        onChange && onChange(undefined);
    }, [onChange, isMultiple, allowArbitraryInput]);

    // Verify if we need to add/remove extra height based on default height.
    const tokenList = useRef<HTMLDivElement>(null);
    const tokenListMeasure = useMeasure(tokenList, false, true);
    const tokenScrollHeight = tokenList.current?.scrollHeight;
    const [inputRowSize, setInputRowSize] = useState<number>(1);
    const [initialHeight, setInitalHeight] = useState<number>(tokenListMeasure.height);
    useEffect(() => {
        if (initialHeight == 0 && tokenListMeasure.height > 0) {
            setInitalHeight(tokenListMeasure.height);
        }
        if (initialHeight && tokenScrollHeight) {
            const rowSize = Math.round(tokenScrollHeight / initialHeight);
            if (inputRowSize !== rowSize && rowSize !== Infinity) {
                setInputRowSize(rowSize);
            }
        }
    }, [initialHeight, tokenListMeasure, tokenScrollHeight, inputRowSize]);

    /**
     * Syncs the arbitrary values with those selected by the user
     */
    useEffect(() => {
        if (allowArbitraryInput) {
            setControlledOptions((prevState) => {
                if (prevState) {
                    return prevState.filter((controlled) => values.includes(controlled));
                }
                return prevState;
            });

            setArbitraryValues(values && Array.isArray(values) ? values : []);
            setValuesState(values && Array.isArray(values) ? values : []);
        }
    }, [values, allowArbitraryInput]);

    /**
     * Handles closing the popover, clearing the query.
     */
    const afterSelectHandler = useCallback(() => {
        if (displayValue) {
            setState({ status: "selected", value: displayValue });
        } else {
            setState({ status: "initial", value: "" });
        }
        if (allowArbitraryInput) {
            setControlledOptions([]);
        }
    }, [displayValue, allowArbitraryInput]);

    /**
     * Select a label and send it's value through onChange.
     */
    const onSelect = useCallback(
        (label: string) => {
            const currentValue = valuesState && !Array.isArray(valuesState) && isMultiple ? [valuesState] : valuesState;
            const value = optionByLabel[label].value;

            let finalValue: any | any[] = isMultiple ? [] : value;
            const indexFound = Array.isArray(valuesState) ? valuesState.indexOf(value) : -1;

            if (Array.isArray(finalValue) && finalValue.length === 0 && currentValue) {
                finalValue = currentValue;
            }
            if (isMultiple) {
                const indexDefaultValueFound = finalValue.indexOf(value);
                if (!Array.isArray(valuesState) && indexDefaultValueFound > -1) {
                    finalValue.splice(indexDefaultValueFound, 1);
                } else {
                    if (Array.isArray(valuesState) && indexFound > -1) {
                        finalValue.splice(indexFound, 1);
                    } else {
                        finalValue.push(value);
                    }
                }
            }
            onChange && onChange(finalValue);
            afterSelectHandler();
        },
        [onChange, optionByLabel, isMultiple, valuesState],
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
     * Provides a context for the children.
     */
    const context = useMemo<IAutoCompleteContext>(
        () => ({
            onClear,
            inputState: state,
            setInputState: setState,
            value: value ? value : state.value,
            size,
            setOptions: setControlledOptions,
            multiple: isMultiple,
        }),
        [state, onClear, value, size, isMultiple],
    );

    /**
     * Filters options using the search string and returns them.
     */
    const selectedTokens = useMemo<string[]>(() => {
        if (!isMultiple || valuesState === undefined) return [];
        const values = Array.isArray(valuesState) ? valuesState : [valuesState];
        return values
            .map((value) => optionByValue[value]?.label ?? "")
            .filter((item) => item !== undefined && item !== "");
    }, [isMultiple, optionByValue, valuesState]);

    /**
     * Removes a specific value and fires off the onChange
     */
    const removeArbitraryInput = (inputValue: string | number) => {
        if (allowArbitraryInput) {
            const newValues = values.filter((v: string | number) => v !== inputValue);
            onChange && onChange(newValues.length === 0 ? undefined : newValues);
        }
    };

    /**
     * Because DOM input value is not necessarily set when using token inputs,
     * we need to control when the placeholder is set or unset
     */
    const placeholderValue = useMemo<string | undefined>(() => {
        if (placeholder) {
            if (selectedTokens.length > 0 || arbitraryValues.length > 0 || state.value) {
                return undefined;
            }
            return placeholder;
        }
        return undefined;
    }, [placeholder, selectedTokens, arbitraryValues, state]);

    return (
        <AutoCompleteContext.Provider value={context}>
            <Reach.Combobox className={classes.reachCombobox} onSelect={onSelect} ref={forwardedRef} openOnFocus>
                <div className={cx(classes.inputContainer, props.className)}>
                    <Reach.ComboboxInput
                        {...otherProps}
                        selectOnClick
                        disabled={disabled}
                        autoFocus={autoFocus}
                        onChange={onInputChange}
                        placeholder={placeholderValue}
                        value={String(state.value)}
                        className={cx(
                            classesInput.input,
                            classes.input,
                            inputClassName,
                            classesInput.inputHeightConstrainst(inputRowSize),
                        )}
                        onKeyDown={(e) => {
                            // Keys are used for combobox navigaiton.
                            // Don't allow these to propogate up the tree.
                            // For example when using this inside of a drag and drop tree
                            // we dont the tree to change the focused element
                            // when trying to select an item
                            e.stopPropagation();
                        }}
                    />
                    <div ref={tokenList} className={classes.inputTokens}>
                        {isMultiple && (
                            <>
                                {selectedTokens.map((labelItem, index) => {
                                    return (
                                        <AutoCompleteToken
                                            key={index}
                                            label={labelItem}
                                            onUnSelect={() => onSelect(labelItem)}
                                        />
                                    );
                                })}
                            </>
                        )}
                        {allowArbitraryInput && (
                            <>
                                {arbitraryValues.map((item, index) => {
                                    return (
                                        <AutoCompleteToken
                                            key={`${index}${item}`}
                                            label={item}
                                            onUnSelect={() => removeArbitraryInput(item)}
                                        />
                                    );
                                })}
                            </>
                        )}
                    </div>
                    {!disabled && (
                        <div className={classes.inputActions}>
                            <AutoCompleteArrow />
                            {clear && value && <AutoCompleteClear onClear={onClear!} />}
                        </div>
                    )}
                </div>
                <Reach.ComboboxPopover
                    className={cx(classes.popover, props.popoverClassName)}
                    data-autocomplete-state={state.status}
                >
                    <Reach.ComboboxList>
                        {filteredOptions.map((props, index) => (
                            <AutoCompleteOption key={index} {...props} />
                        ))}
                    </Reach.ComboboxList>
                </Reach.ComboboxPopover>
            </Reach.Combobox>
            {optionProvider}
        </AutoCompleteContext.Provider>
    );
}) as Polymorphic.ForwardRefComponent<"input", IAutoCompleteProps>;
