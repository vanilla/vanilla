/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */
import DatePicker from "@library/forms/DatePicker";
import InputBlock from "@library/forms/InputBlock";
import { inputClasses } from "@library/forms/inputStyles";
import { useStackingContext } from "@vanilla/react-utils";
import React from "react";
import { css } from "@emotion/css";

interface IProps {
    value: string;
    onChange(newValue: string): void;
    disabled?: boolean;
    placeholder?: string;
    inputAriaLabel?: string;
}

export function DashboardDatePicker(props: IProps) {
    const inputClassNames = inputClasses();
    const { zIndex } = useStackingContext();

    return (
        <div className="input-wrap">
            <InputBlock noMargin>
                <DatePicker
                    onChange={props.onChange}
                    value={props.value}
                    inputClassName={inputClassNames.inputText}
                    datePickerDropdownClassName={css({
                        zIndex: zIndex,
                        top: -350, //render above the input
                    })}
                    inputAriaLabel={props.inputAriaLabel}
                />
            </InputBlock>
        </div>
    );
}
