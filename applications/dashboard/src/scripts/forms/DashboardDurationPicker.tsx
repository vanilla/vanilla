/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import InputBlock from "@library/forms/InputBlock";
import { DurationPicker, DurationPickerValue } from "@library/forms/durationPicker/DurationPicker";
import { IFieldError } from "@library/@types/api/core";
import { DurationPickerUnit } from "@library/forms/durationPicker/DurationPicker.types";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";

interface IProps {
    value: DurationPickerValue;
    onChange(newValue: DurationPickerValue): void;
    disabled?: boolean;
    placeholder?: string;
    inputAriaLabel?: string;
    errors?: IFieldError[];
    supportedUnits?: string[];
}

export function DashboardDurationPicker(props: IProps) {
    return (
        <DashboardInputWrap>
            <InputBlock noMargin errors={props.errors}>
                <DurationPicker
                    value={props.value}
                    onChange={props.onChange}
                    supportedUnits={props.supportedUnits as DurationPickerUnit[]}
                    disabled={props.disabled}
                />
            </InputBlock>
        </DashboardInputWrap>
    );
}
