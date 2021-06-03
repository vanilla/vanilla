/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ChangeEvent, FocusEvent, useContext, useEffect, useRef, useState } from "react";
import { mergeRefs } from "@vanilla/react-utils";
import { cx } from "@emotion/css";
import { ComboboxInput, useComboboxContext } from "@reach/combobox";
import { autoCompleteClasses } from "./AutoComplete.styles";
import { DropDownArrow } from "../shared/DropDownArrow";
import { ClearIcon } from "../shared/ClearIcon";
import { AutoCompleteContext } from "./AutoComplete";
import { inputClasses } from "../shared/input.styles";

export interface IAutoCompleteInputProps extends React.ComponentProps<typeof ComboboxInput> {
    arrow?: boolean | React.ReactNode;
    clear?: boolean | React.ReactNode | ((props: { onClear(): void }) => React.ReactNode);
}

function AutoCompleteArrow(props: React.PropsWithChildren<{}>) {
    const { children } = props;
    const { isExpanded } = useComboboxContext();
    const { size } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    return (
        <div className={classes.autoCompleteArrow}>
            {typeof children === "function" ? children({ isExpanded }) : <DropDownArrow />}
        </div>
    );
}

function AutoCompleteClear(props: React.PropsWithChildren<{ onClear(): void }>) {
    const { children, onClear } = props;
    const { isExpanded } = useComboboxContext();
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
            {typeof children === "function" ? (
                children({ isExpanded, onClear })
            ) : (
                <ClearIcon style={{ color: "#777a80" }} />
            )}
        </div>
    );
}

/**
 * Wraps an input for a searchable dropdown.
 * See ReachUI's ComboboxInput: https://reach.tech/combobox#comboboxinput
 */
export const AutoCompleteInput = React.forwardRef(function AutoCompleteInputImpl(
    props: IAutoCompleteInputProps,
    forwardedRef: React.Ref<HTMLInputElement>,
) {
    const { arrow, clear, ...otherProps } = props;
    const { value, setValue, onClear, size } = useContext(AutoCompleteContext);
    const classes = autoCompleteClasses({ size });
    const classesInput = inputClasses({ size });
    const inputRef = useRef<HTMLInputElement>();

    // We need to control the value to be able to clear it.
    useEffect(() => {
        setValue!(props.value || "");
    }, [props.value, setValue]);
    const onChange = (event: ChangeEvent<HTMLInputElement>) => {
        setValue!(event.target.value || "");
        props.onChange && props.onChange(event);
    };

    return (
        <div className={classes.inputContainer}>
            <ComboboxInput
                {...otherProps}
                ref={mergeRefs(forwardedRef, inputRef)}
                onChange={onChange}
                value={value ?? props.value}
                className={cx(classesInput.input, classes.input, props.className)}
            />
            <div className={classes.inputActions}>
                {arrow && <AutoCompleteArrow>{typeof arrow === "boolean" ? null : arrow}</AutoCompleteArrow>}
                {clear && value && (
                    <AutoCompleteClear onClear={onClear!}>
                        {typeof clear === "boolean" ? null : clear}
                    </AutoCompleteClear>
                )}
            </div>
        </div>
    );
});
