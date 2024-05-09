/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { IButtonProps } from "@library/forms/Button";
import { IInputTextProps } from "@library/forms/InputTextBlock";
import { ISelectOneProps } from "@library/forms/select/SelectOne";
import { t } from "@library/utility/appUtils";

export enum DurationPickerUnit {
    MINUTES = "minute",
    HOURS = "hour",
    DAYS = "day",
    WEEKS = "week",
    MONTHS = "month",
    YEARS = "year",
}

export const unitOptions: IComboBoxOption[] = Object.entries(DurationPickerUnit).map(([key, value]) => ({
    label: t(key.toLowerCase()),
    value,
}));

export interface IDurationValue {
    length?: number;
    unit?: DurationPickerUnit;
}

export interface IDurationPickerProps {
    onChange: (value: IDurationValue) => void;
    value?: IDurationValue;
    min?: number;
    max?: number;
    className?: string;
    id?: string;
    lengthInputProps?: Omit<IInputTextProps, "value" | "onChange">;
    unitInputProps?: Omit<ISelectOneProps, "options" | "onChange" | "value" | "label" | "isClearable">;
    submitButton?: Omit<IButtonProps, "buttonType" | "onClick" | "submit"> & {
        tooltip?: string;
        onClick: (newValue: IDurationValue) => void;
    };
}
