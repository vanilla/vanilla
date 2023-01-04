/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IControlGroupProps, IControlProps } from "@vanilla/json-schema-forms";
import InputBlock from "@library/forms/InputBlock";
import { TextInput, InputValidationFilter } from "@library/forms/TextInput";
import DatePicker from "@library/forms/DatePicker";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne from "@library/forms/select/SelectOne";
import InputTextBlock from "@library/forms/InputTextBlock";
import Checkbox from "@library/forms/Checkbox";
import { Tokens } from "@library/forms/select/Tokens";
import { ProfileFieldVisibilityIcon } from "@dashboard/userProfiles/components/ProfileFieldVisibilityIcon";
import moment from "moment";

const createOptions = (options): IComboBoxOption[] => {
    return options
        ? Object.values(options).map((choice: string) => ({
              value: choice,
              label: choice,
          }))
        : [];
};

export function FormControl(props: IControlProps) {
    const { disabled, onChange, control, instance, required } = props;

    switch (control.inputType) {
        case "text":
        case "number": {
            const validationFilter = control.inputType === "number" ? ("number" as InputValidationFilter) : undefined;
            return (
                <TextInput
                    disabled={disabled}
                    value={instance ?? ""}
                    validationFilter={validationFilter}
                    onChange={(event) => onChange(event.target.value)}
                    required={required}
                />
            );
        }
        case "text-multiline": {
            return (
                <InputTextBlock
                    inputProps={{
                        multiline: true,
                        disabled: disabled,
                        value: instance ?? "",
                        onChange: (event: React.ChangeEvent<HTMLInputElement>) => onChange(event.target.value),
                    }}
                    multiLineProps={{
                        overflow: "scroll",
                        rows: 5,
                        maxRows: 5,
                    }}
                />
            );
        }
        case "date": {
            const readOnlyValue = instance
                ? moment(instance).format("YYYY-M-D") //this format matches the DatePicker's readout
                : "";

            return disabled ? (
                <TextInput value={readOnlyValue} disabled />
            ) : (
                <DatePicker alignment="right" onChange={(value) => onChange(value)} value={instance} />
            );
        }
        case "checkbox": {
            return (
                <Checkbox
                    label={control.label}
                    onChange={(event) => onChange(event.target.checked)}
                    disabled={disabled}
                    checked={instance}
                />
            );
        }
        case "dropdown": {
            const options = createOptions(control.choices.staticOptions);
            return (
                <SelectOne
                    label={null}
                    disabled={disabled}
                    value={options.find((opt) => opt.value === String(instance))}
                    onChange={(event) => onChange(event.value)}
                    options={options}
                />
            );
        }
        case "tokens": {
            const options = createOptions(control.choices.staticOptions);

            return (
                <Tokens
                    label={control.label ?? ""}
                    disabled={disabled}
                    value={instance}
                    onChange={(options) => onChange(options)}
                    options={options}
                />
            );
        }
        default:
            return <div>{(control as any).inputType} is not supported</div>;
    }
}

export function FormControlGroup(props: React.PropsWithChildren<IControlGroupProps>) {
    const { children, controls, validation, schema } = props;
    const { inputType, label, description } = controls[0];

    const createLabel = () => {
        if (inputType === "checkbox") {
            return "";
        }
        return label ?? "";
    };

    return inputType === "tokens" ? (
        <>{children}</>
    ) : (
        <InputBlock
            label={createLabel()}
            labelNote={description}
            icon={<ProfileFieldVisibilityIcon visibility={schema.visibility} />}
            errors={
                validation?.errors
                    ?.filter((error) => error.instancePath === `/${props.path[0]!}`)
                    .map((e) => {
                        return {
                            message: e.message!,
                            field: `${props.path[0]!}`,
                        };
                    }) ?? []
            }
        >
            {children}
        </InputBlock>
    );
}
