/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useCallback, useContext, useEffect, useMemo, useRef, useState } from "react";
import * as Reach from "@reach/combobox";
import * as Polymorphic from "../../polymorphic";
import { positionMatchWidth } from "@reach/popover";
import { useRect } from "@reach/rect";
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
import { useComboboxContext } from "@reach/combobox";
import groupBy from "lodash/groupBy";
import sortBy from "lodash/sortBy";
import { useStackingContext } from "@vanilla/react-utils";

function AutoCompleteArrow() {
    const { size } = useContext(AutoCompleteContext);
    const { zIndex } = useStackingContext();
    const classes = useMemo(() => autoCompleteClasses({ size, zIndex }), [size, zIndex]);
    return (
        <div className={classes.autoCompleteArrow}>
            <DropDownArrow />
        </div>
    );
}

function AutoCompleteClear(props: { onClear(): void }) {
    const { onClear } = props;
    const { size } = useContext(AutoCompleteContext);
    const { zIndex } = useStackingContext();
    const classes = useMemo(() => autoCompleteClasses({ size, zIndex }), [size, zIndex]);
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
    const { zIndex } = useStackingContext();
    const classes = useMemo(() => autoCompleteClasses({ size, zIndex }), [size, zIndex]);
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

type ComboboxStatus = "IDLE" | "SUGGESTING" | "NAVIGATING" | "INTERACTING";

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
        id,
        ...otherProps
    } = props;
    const { zIndex } = useStackingContext();
    const classes = useMemo(
        () => autoCompleteClasses({ size, isDisabled: disabled, isClearable: !!clear, zIndex }),
        [size, disabled, clear, zIndex],
    );
    const classesInput = useMemo(() => inputClasses({ size }), [size]);
    const [controlledOptions, setControlledOptions] = useState<IAutoCompleteOptionProps[]>();
    const [arbitraryValues, setArbitraryValues] = useState<string[]>([]);
    const [comboboxState, setComboboxState] = useState<ComboboxStatus>();
    // This ref records the outmost container so that the pop over can use its size and placement
    const containerRef = useRef() as RefObject<HTMLDivElement>;
    const containerRect = useRect(containerRef);
    // This ref records the HTML input so that we can focus it when clicking on the parent container
    const inputRef = useRef() as RefObject<HTMLInputElement>;
    // This state tracks if a user is using the direction keys tp navigate the combo box
    const [isUsingDirectionKeys, setUsingDirectionKeys] = useState(false);

    const { options, optionByValue, optionByLabel } = useMemo(() => {
        let options = [...(props.options ? props.options : []), ...(controlledOptions ? controlledOptions : [])];

        // Remove duplicate for a prop option that has been chosen and sent back as controlledOption
        if (controlledOptions && controlledOptions.length) {
            options = options.filter((obj, index) => options.findIndex((item) => item.value === obj.value) === index);
        }
        return makeOptionState(options, optionProvider);
    }, [controlledOptions, optionProvider, props.options]);

    const values = value;

    // this prevents switching from multiple to non-multiple when the value is cleared
    const isMultiple = useMemo(() => {
        return props.multiple || Array.isArray(value);
    }, [props, value]);

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
            //when suggesting, we should not change the selection
            if (state.status !== "suggesting") {
                setState({ status: "selected", value: displayValue });
            }
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
        onChange && onChange(isMultiple ? [] : undefined);
    }, [onChange, isMultiple, allowArbitraryInput]);

    /**
     * Syncs the arbitrary values with those selected by the user
     */
    useEffect(() => {
        if (allowArbitraryInput) {
            setControlledOptions((prevState) => {
                if (prevState && values && Array.isArray(values)) {
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
                    finalValue = finalValue.filter((_val, index) => index !== indexDefaultValueFound);
                } else {
                    if (Array.isArray(valuesState) && indexFound > -1) {
                        finalValue = finalValue.filter((_val, index) => index !== indexFound);
                    } else {
                        finalValue = [...finalValue, value];
                    }
                }
            }

            if (finalValue === "true") {
                finalValue = true;
            }
            if (finalValue === "false") {
                finalValue = false;
            }

            onChange?.(finalValue);
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
            .filter((item) => item !== undefined && item !== "" && !arbitraryValues.map(String).includes(item));
    }, [isMultiple, optionByValue, valuesState, arbitraryValues]);

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
            if (selectedTokens.length > 0 || arbitraryValues.length > 0 || (!isMultiple && state.value)) {
                return undefined;
            }
            return placeholder;
        }
        return undefined;
    }, [placeholder, selectedTokens, arbitraryValues, isMultiple, state]);

    const handleKeyDown = (event) => {
        /**
         * When not using the arrow keys, the return key should select the
         * first filtered option
         */
        setUsingDirectionKeys([38, 40].includes(event.keyCode));
        if (
            !isUsingDirectionKeys &&
            inputRef?.current?.value.length !== 0 &&
            comboboxState !== "IDLE" &&
            event.keyCode === 13
        ) {
            filteredOptions.length && onSelect(filteredOptions[0].label ?? filteredOptions[0].value);
            setUsingDirectionKeys(false);
        }
        /**
         * When the user hits delete and the text input is empty, the last token should be delete
         */
        if (event.keyCode === 8 && inputRef?.current?.value.length === 0) {
            // Get the last token value
            const lastValue = [values].flat().pop();
            // If there is a defined options list, remove its selection by label
            if (isMultiple) {
                if (lastValue) {
                    const { label } = optionByValue[lastValue] || {};
                    label && onSelect(label);
                }
            }
            // If its arbitrary, remove it by value
            if (allowArbitraryInput) {
                lastValue && removeArbitraryInput(lastValue);
            }
        }
        // Keys are used for combobox navigation.
        // Don't allow these to propagate up the tree.
        // For example when using this inside of a drag and drop tree
        // we dont the tree to change the focused element
        // when trying to select an item
        event.stopPropagation();
    };

    const groupedFilteredOptions = groupBy(filteredOptions, (option) => option.group);

    const showClearButton = !!clear && !!(isMultiple ? value && value.length > 0 : value);

    return (
        <AutoCompleteContext.Provider value={context}>
            <Reach.Combobox className={classes.reachCombobox} onSelect={onSelect} ref={forwardedRef} openOnFocus>
                <div
                    className={cx(classes.inputContainer, props.className)}
                    ref={containerRef}
                    onClick={(e: React.MouseEvent) => {
                        e.preventDefault();
                        e.stopPropagation();
                        inputRef?.current && inputRef?.current?.focus();
                    }}
                >
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
                    <Reach.ComboboxInput
                        {...otherProps}
                        id={id}
                        ref={inputRef}
                        selectOnClick
                        disabled={disabled}
                        autoFocus={autoFocus}
                        onChange={onInputChange}
                        placeholder={placeholderValue}
                        value={String(state.value)}
                        className={cx(classesInput.input, classes.input, inputClassName)}
                        onKeyDown={handleKeyDown}
                    />

                    {!disabled && (
                        <div className={classes.inputActions}>
                            <AutoCompleteArrow />
                            {showClearButton && <AutoCompleteClear onClear={onClear!} />}
                        </div>
                    )}
                </div>
                <Reach.ComboboxPopover
                    className={cx(classes.popover, props.popoverClassName)}
                    data-autocomplete-state={state.status}
                    /**
                     * This provides the popover the size and positioning of the parent wrapper
                     * instead of the input itself, which changes size with token inputs
                     * https://github.com/reach/reach-ui/pull/845#issuecomment-939074638
                     */
                    position={(_, popoverRect) => positionMatchWidth(containerRect, popoverRect)}
                >
                    <Reach.ComboboxList>
                        {/* the options without a group come first */}
                        {sortBy(Object.entries(groupedFilteredOptions), ([groupName]) => groupName !== "undefined").map(
                            ([groupName, groupMembers], index) => (
                                <React.Fragment key={index}>
                                    <li role="separator" className={classes.separator} />
                                    {groupName !== "undefined" && (
                                        <h5 className={classes.groupHeading}> {groupName}</h5>
                                    )}
                                    {groupMembers.map((props, index) => (
                                        <AutoCompleteOption key={index} {...props} />
                                    ))}
                                </React.Fragment>
                            ),
                        )}
                    </Reach.ComboboxList>
                </Reach.ComboboxPopover>
                <ComboboxState status={setComboboxState} />
            </Reach.Combobox>
            {optionProvider}
        </AutoCompleteContext.Provider>
    );
}) as Polymorphic.ForwardRefComponent<"input", IAutoCompleteProps>;

/**
 * This kludge component is used to report the state of a ReachUI combo box to
 * the AutoComplete, the hook herein needs to be a child of the combobox being observed
 */
interface IComboboxStateProps {
    status(state: ComboboxStatus): void;
}
function ComboboxState(props: IComboboxStateProps) {
    const { state } = useComboboxContext();
    useEffect(() => {
        props.status(state);
    }, [state]);
    return null;
}
