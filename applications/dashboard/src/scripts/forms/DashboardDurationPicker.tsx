/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import InputBlock from "@library/forms/InputBlock";
import { DurationPicker } from "@library/forms/durationPicker/DurationPicker";
import { IFieldError } from "@library/@types/api/core";
import { DurationPickerUnit } from "@library/forms/durationPicker/DurationPicker.types";

interface IProps {
    value: string;
    onChange(newValue: string): void;
    disabled?: boolean;
    placeholder?: string;
    inputAriaLabel?: string;
    errors?: IFieldError[];
    supportedUnits?: string[];
}

export function DashboardDurationPicker(props: IProps) {
    return (
        <div className="input-wrap">
            <InputBlock noMargin errors={props.errors}>
                <DurationPicker
                    value={props.value}
                    onChange={props.onChange}
                    supportedUnits={props.supportedUnits as DurationPickerUnit[]}
                    disabled={props.disabled}
                />
            </InputBlock>
        </div>
    );
}
