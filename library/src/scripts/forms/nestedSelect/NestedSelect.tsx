/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { inputClasses } from "@library/forms/inputStyles";
import {
    INestedSelectOptionProps,
    INestedSelectProps,
    nestedSelectClasses,
    NestedSelectOption,
    useNestedOptions,
} from "@library/forms/nestedSelect";
import { DownTriangleIcon } from "@library/icons/common";
import Paragraph from "@library/layout/Paragraph";
import { TokenItem } from "@library/metas/TokenItem";
import { ToolTip } from "@library/toolTip/ToolTip";
import { useUniqueID } from "@library/utility/idUtils";
import { Popover, positionMatchWidth } from "@reach/popover";
import { t } from "@vanilla/i18n";
import { RecordID, stableObjectHash } from "@vanilla/utils";
import { ChangeEventHandler, KeyboardEventHandler, RefObject, useEffect, useMemo, useRef, useState } from "react";
import type { Select } from "@vanilla/json-schema-forms";

export function NestedSelect(props: INestedSelectProps) {
    const {
        classes: classOverrides,
        prefix = "select",
        label,
        labelNote,
        noteAfterInput,
        errors,
        multiple,
        onChange,
        value,
        defaultValue,
        createable,
    } = props;
    const [isFocused, setIsFocused] = useState<boolean>(props.autoFocus ?? false);
    const [inputValue, setInputValue] = useState<string>("");
    const [isOpen, setIsOpen] = useState<boolean>(false);
    const [isClearable, setIsClearable] = useState<boolean>(false);
    const [highlightedValue, setHighlightedValue] = useState<RecordID>();

    const classes = nestedSelectClasses({
        compact: props.compact,
        maxHeight: props.maxHeight,
    });
    const classesInputBlock = inputBlockClasses();
    const classesInput = inputClasses();

    const inputRef = useRef() as RefObject<HTMLInputElement>;
    const selectRef = useRef() as RefObject<HTMLDivElement>;
    const menuRef = useRef() as RefObject<HTMLUListElement>;
    const generatedName = useUniqueID(prefix);
    const name = props.name ?? generatedName;
    const generatedID = useUniqueID(name);
    const id = props.id ?? generatedID;
    const labelID = props.labelID ?? `${id}-label`;
    const inputID = props.inputID ?? `${id}-input`;
    const errorID = `${id}-errors`;
    const optionID = `${id}-option`;
    const [createdOptions, setCreatedOptions] = useState<Select.Option[]>([]);

    let { options, optionsByValue, optionsByGroup } = useNestedOptions({
        searchQuery: inputValue,
        options: props.options,
        optionsLookup: props.optionsLookup,
        createable,
        createdOptions,
    });

    // Set and unset event listeners clicking outside when the component mounts and unmounts
    useEffect(() => {
        document.addEventListener("mousedown", clickOutsideListener);
        document.addEventListener("touchend", clickOutsideListener);

        return () => {
            document.removeEventListener("mousedown", clickOutsideListener);
            document.removeEventListener("touchend", clickOutsideListener);
        };
    }, []);

    // default the highlighted option to the first option when the options list changes
    useEffect(() => {
        setHighlightedValue(options[0]?.value);
    }, [options]);

    // If clicking outside of the dropdown, close it and remove focus
    const clickOutsideListener = (event) => {
        if (
            !selectRef.current ||
            selectRef.current.contains(event.target) ||
            !menuRef.current ||
            menuRef.current.contains(event.target)
        ) {
            return;
        }

        setIsFocused(false);
        setIsOpen(false);
        inputRef.current?.blur();
    };

    // Update selected value or list of tokens if the value is changed externally
    useEffect(() => {
        let hasValue = false;
        if (Array.isArray(value)) {
            hasValue = value.length > 0;
        } else {
            hasValue = value !== undefined;
        }
        setIsClearable(Boolean(props.isClearable) && hasValue);
    }, [value]);

    const selectedOption = optionsByValue[value as RecordID];

    const selectedTokens = useMemo<INestedSelectOptionProps[]>(() => {
        if (multiple && Array.isArray(value)) {
            return value.map((val) => optionsByValue[val]);
        }
        return [];
    }, [value, optionsByValue]);

    // When changing the input value, filter the options by the input value
    const handleOnInputChange: ChangeEventHandler<HTMLInputElement> = (event) => {
        const {
            target: { value },
        } = event;
        setInputValue(value);

        // make sure to open the menu when changing the input
        if (!isOpen && value.length > 0) {
            setIsOpen(true);
        }

        props.onInputChange?.(value);
        props.onSearch?.(value);
    };

    // Actions to take for specific keyboard presses when in the input
    const handleOnKeyDown: KeyboardEventHandler<HTMLInputElement> = (event) => {
        if (!isOpen && (event.key === "Enter" || event.key === "ArrowDown")) {
            setIsOpen(true);
            event.preventDefault();
        }

        // Pressing enter key should select the highlighted option
        if (event.key === "Enter" && highlightedValue) {
            handleOnSelect(highlightedValue);
            event.preventDefault();
        }

        // Pressing the backspace key should remove the last token if the input is empty
        if (event.key === "Backspace" && multiple && selectedTokens.length && !inputValue.length) {
            const lastToken = selectedTokens.pop();
            if (lastToken?.value) {
                handleOnSelect(lastToken.value);
            }
        }

        // If the Escape key is pressed, clear the input if there is a value, otherwise close the menu
        if (event.key === "Escape") {
            if (inputValue.length > 0) {
                setInputValue("");
            } else {
                setIsOpen(false);
            }
            event.preventDefault();
        }

        // If the Tab key is pressed to change focus, close the menu
        if (event.key === "Tab" && isOpen) {
            setIsOpen(false);
        }

        // If the Arrow Keys are pressed, navigate the menu
        if (["ArrowDown", "ArrowUp"].includes(event.key) && isOpen) {
            const currentIdx = options.findIndex((opt) => opt.value === highlightedValue);

            // Get the option that we are navigating to
            const currentOption = arrowNavigation(event.key, currentIdx);

            // Get a valid option to highlight
            setHighlightedValue(currentOption?.value);

            // Let's make sure that the menu scrolls with the arrow navigation
            if (menuRef.current) {
                // get a list of all of the list items in the menu
                const allItems = Array.from(menuRef.current.getElementsByTagName("li"));
                // a regex for the currently highlighted option
                const regex = new RegExp(`${optionID}-${currentOption?.value}`);
                // get the index value for the highlighted list item
                const itemIdx = allItems.findIndex(({ id }) => id.match(regex));
                // cut down the list of items to just those items up to the highlighted option
                const listItems = allItems.slice(0, itemIdx + 1);
                // get the total height of the selected options
                const itemsHeight = listItems.reduce((acc, itm) => {
                    const height = itm.getBoundingClientRect().height;
                    return (acc += height);
                }, 0);
                // get the element of the highlighted option
                const itemEl = document.getElementById(`${optionID}-${currentOption?.value}`);
                itemEl?.focus();

                // if using the down arrow and highlighting beyond what is below what is visible, scroll up by one
                if (event.key === "ArrowDown" && itemsHeight > menuRef.current.getBoundingClientRect().height) {
                    itemEl?.scrollIntoView(false);
                }
                // if using the up arrow and highlighting beyond what is above what is visible, scroll down by one
                else if (
                    event.key === "ArrowUp" &&
                    itemsHeight - (itemEl?.getBoundingClientRect().height ?? 0) < menuRef.current.scrollTop
                ) {
                    itemEl?.scrollIntoView();
                }
            }
        }

        event.stopPropagation();
    };

    // Navigate to the appropriate option using the keyboard arrow keys
    const arrowNavigation = (dir: string, index: number): INestedSelectOptionProps => {
        const max = options.length - 1;
        let newIndex = index;

        // Get the next index value when the down arrow is pressed and we are not at the end
        if (dir === "ArrowDown" && index < max) {
            newIndex += 1;
        }
        // Get the previous index value when the up arrow is pressed and we are not at the beginning
        else if (dir === "ArrowUp" && index > 0) {
            newIndex -= 1;
        }

        // Get the current option based on the updated index
        const currentOption = options[newIndex];

        // If the current option is a header then we need to repeat the process again
        if (!currentOption || currentOption.isHeader) {
            // If we are at the beginning or end of the list, return the previous value
            if (newIndex === max || newIndex === 0) {
                return options[index];
            }
            // otherwise repeat the process
            return arrowNavigation(dir, newIndex);
        }

        // Return the found highlighted option
        return currentOption;
    };

    // Select or deselect an option and update the appropriate props
    const handleOnSelect = async (option: RecordID) => {
        setCreatedOptions((opts) => {
            if (!createable) {
                return opts;
            }
            if (createdOptions.find((opt) => opt.value === option)) {
                // We already have it.
                return opts;
            }

            // If the option is not creatable don't add it.
            if (!optionsByValue[option]?.data?.createable) {
                return opts;
            }

            // Otherwise add it.
            return [...opts, { value: option, label: option } as Select.Option];
        });

        let data: any;
        let tmpValue: INestedSelectProps["value"] = multiple
            ? (Array.from((value ?? defaultValue ?? []) as Iterable<RecordID>) as RecordID[])
            : value ?? defaultValue;

        if (Array.isArray(tmpValue)) {
            if (tmpValue.includes(option)) {
                tmpValue = tmpValue.filter((opt) => opt !== option);
            } else {
                tmpValue.push(option);
            }
            data = tmpValue.map((val) => optionsByValue[val as RecordID]);
        } else {
            tmpValue = tmpValue === option ? defaultValue : option;
            data = optionsByValue[tmpValue as RecordID];
        }

        // send the new value to the parent component and clear the input and close the menu
        await onChange(tmpValue, data);
        setInputValue("");
        setIsOpen(false);
        setHighlightedValue(undefined);
    };

    // Clear the selected options and replace with either the default values or an empty value
    const clearSelection = () => {
        const tmpValue: INestedSelectProps["value"] = multiple
            ? (Array.from((defaultValue ?? []) as Iterable<RecordID>) as RecordID[])
            : defaultValue;
        const data: any = Array.isArray(tmpValue)
            ? tmpValue.map((val) => optionsByValue[val])
            : optionsByValue[tmpValue as RecordID];

        // send the new value back to the parent component and remove the Clear button
        onChange(tmpValue, data);
        setIsClearable(false);
    };

    return (
        <>
            <div id={id} className={cx(classesInputBlock.root, classes.root, classOverrides?.root)}>
                {label && (
                    <label
                        id={labelID}
                        htmlFor={inputID}
                        className={cx(classesInputBlock.labelAndDescription, classes.label, classOverrides?.label)}
                    >
                        <span className={classesInputBlock.labelText}>{label}</span>
                        {labelNote && <Paragraph className={classesInputBlock.labelNote}>{labelNote}</Paragraph>}
                    </label>
                )}
                <div className={classesInputBlock.inputWrap}>
                    <div
                        className={cx(classesInput.inputContainer, classes.inputContainer, classOverrides?.input, {
                            hasFocus: isFocused,
                            [classes.inputError]: errors != null && errors.length > 0,
                        })}
                        onClick={() => {
                            if (inputRef.current) {
                                if (!isFocused && !isOpen) {
                                    inputRef.current.focus();
                                    setIsFocused(true);
                                    setIsOpen(true);
                                } else {
                                    setIsFocused(false);
                                    setIsOpen(false);
                                }
                            }
                        }}
                        ref={selectRef}
                        data-testid="inputContainer"
                    >
                        <div className={classes.input}>
                            {multiple && (
                                <>
                                    {selectedTokens.map((token, idx) => {
                                        if (!token) {
                                            return null;
                                        }
                                        const { label, value } = token;
                                        return (
                                            <TokenItem
                                                key={stableObjectHash(token)}
                                                className={classes.token}
                                                compact={props.compact}
                                                onRemove={() => handleOnSelect(value as RecordID)}
                                            >
                                                {label}
                                            </TokenItem>
                                        );
                                    })}
                                </>
                            )}
                            <input
                                type="text"
                                id={inputID}
                                aria-label={props.ariaLabel ?? label}
                                aria-labelledby={labelID}
                                aria-describedby={errorID ?? props.ariaDescribedBy}
                                onChange={handleOnInputChange}
                                onKeyDown={handleOnKeyDown}
                                value={inputValue}
                                tabIndex={props.tabIndex}
                                disabled={props.disabled}
                                autoComplete="off"
                                ref={inputRef}
                                placeholder={
                                    multiple
                                        ? // Token is selected
                                          selectedTokens.length > 0
                                            ? undefined
                                            : props.placeholder
                                        : // Single value is selected
                                        selectedOption != null
                                        ? undefined
                                        : props.placeholder
                                }
                            />
                            {!multiple && inputValue.length === 0 && selectedOption?.label && (
                                <span className={classes.selectedValue}>{selectedOption.label}</span>
                            )}
                        </div>
                        <div className={classes.inputIcon}>
                            <DownTriangleIcon />
                        </div>
                        <Popover
                            as="ul"
                            className={classes.menu}
                            role="menu"
                            ref={menuRef}
                            targetRef={selectRef}
                            hidden={!isOpen}
                            position={positionMatchWidth}
                        >
                            {options.length ? (
                                options.map((option, idx) => {
                                    const isSelected = option.isHeader
                                        ? undefined
                                        : Array.isArray(value)
                                        ? value.includes(option.value!)
                                        : option.value === value;
                                    return (
                                        <NestedSelectOption
                                            key={idx}
                                            onHover={() => {
                                                setHighlightedValue(option.value);
                                            }}
                                            {...option}
                                            id={optionID}
                                            isNested={Object.keys(optionsByGroup).length > 0}
                                            onClick={(selectedID) => {
                                                handleOnSelect(selectedID);
                                            }}
                                            isSelected={isSelected}
                                            classes={classes}
                                            searchQuery={inputValue}
                                            highlighted={highlightedValue === option.value}
                                        />
                                    );
                                })
                            ) : (
                                <li className={classes.menuNoOption}>{t("No options")}</li>
                            )}
                        </Popover>
                    </div>
                    {isClearable && (
                        <ToolTip label={multiple ? t("Clear all selected values.") : t("Clear the selected value.")}>
                            <Button
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                className={classes.clearButton}
                                onClick={clearSelection}
                            >
                                {multiple ? t("Clear All") : t("Clear")}
                            </Button>
                        </ToolTip>
                    )}
                    {noteAfterInput && <Paragraph className={classesInputBlock.labelNote}>{noteAfterInput}</Paragraph>}
                    {errors && <ErrorMessages id={errorID} errors={errors} />}
                </div>
            </div>
        </>
    );
}
