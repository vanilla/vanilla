/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { RecordID } from "@vanilla/utils";
import uniqueId from "lodash/uniqueId";
import React, { useContext, useDebugValue, useMemo, useRef, useState } from "react";
import { CustomRadioGroupContext } from "./CustomRadioGroup";
import { customRadioGroupClasses } from "./CustomRadioGroup.classes";

interface ChildContext {
    isSelected: boolean;
    isFocused: boolean;
}

interface IProps extends React.HTMLAttributes<HTMLLabelElement> {
    value: RecordID;
    accessibleDescription?: string;
    children: (context: ChildContext) => React.ReactNode;
}

export const CustomRadioInput = React.forwardRef(function CustomRadioInput(
    props: IProps,
    ref: React.RefObject<HTMLLabelElement>,
) {
    const { value, accessibleDescription, ...htmlProps } = props;
    const ownRef = useRef<HTMLLabelElement>(null);
    ref = ref ?? ownRef;
    const inputRef = useRef<HTMLInputElement>(null);
    const [isFocused, setIsFocused] = useState(false);
    const id = useMemo(() => {
        return "customRadio-" + uniqueId();
    }, []);
    const descriptionID = id + "description";
    const radioContext = useContext(CustomRadioGroupContext);
    const classes = useMemo(() => customRadioGroupClasses(), []);

    const isSelected = value === radioContext.value;
    const debugValue = {
        isSelected,
        value,
        radioContextValue: radioContext.value,
        isFocused,
    };
    useDebugValue(debugValue);

    return (
        <>
            <label {...htmlProps} htmlFor={id} ref={ref}>
                <div id={descriptionID} className={classes.accessibleDescription}>
                    {accessibleDescription}
                </div>
                <input
                    aria-describedby={descriptionID}
                    onFocus={() => {
                        setIsFocused(true);
                    }}
                    onClick={(e) => {
                        //we don't want to have the focus on mouse click
                        if (e.screenX != 0 && e.screenY != 0) {
                            setIsFocused(false);
                        }
                    }}
                    onBlur={() => setIsFocused(false)}
                    ref={inputRef}
                    tabIndex={0}
                    className={classes.input}
                    checked={isSelected}
                    id={id}
                    type="radio"
                    name={radioContext.name}
                    value={value}
                    onChange={(event) => {
                        radioContext.onChange(event.target.value);
                    }}
                />
                {props.children({ isSelected, isFocused: isSelected && isFocused })}
            </label>
        </>
    );
});
