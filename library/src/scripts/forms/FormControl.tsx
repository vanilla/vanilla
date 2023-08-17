/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IControlGroupProps, IControlProps, ICustomControl } from "@vanilla/json-schema-forms";
import InputBlock from "@library/forms/InputBlock";
import { TextInput } from "@library/forms/TextInput";
import DatePicker from "@library/forms/DatePicker";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne from "@library/forms/select/SelectOne";
import InputTextBlock from "@library/forms/InputTextBlock";
import Checkbox from "@library/forms/Checkbox";
import { Tokens } from "@library/forms/select/Tokens";
import { ProfileFieldVisibilityIcon } from "@dashboard/userProfiles/components/ProfileFieldVisibilityIcon";
import moment from "moment";
import LazyDateRange from "@library/forms/LazyDateRange";
import { useStackingContext } from "@vanilla/react-utils";
import { css } from "@emotion/css";

const createOptionsFromRecord = (options?: Record<string, string>): IComboBoxOption[] => {
    return options
        ? Object.entries(options).map(([value, label]) => ({
              value,
              label,
          }))
        : [];
};

export function FormControl(props: IControlProps) {
    const { disabled, onChange, control, instance, required, inModal, dateRangeDirection = "above" } = props;

    const { zIndex } = useStackingContext();

    switch (control.inputType) {
        case "textBox": {
            if (control.type === "textarea") {
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
            } else {
                return (
                    <TextInput
                        disabled={disabled}
                        value={instance}
                        type={control.type}
                        onChange={(event) => {
                            let newValue: string | number | undefined =
                                event.target[control.type === "number" ? "valueAsNumber" : "value"];
                            if (control.type === "number" && Number.isNaN(newValue)) {
                                newValue = undefined;
                            }
                            onChange(newValue);
                        }}
                        required={required}
                    />
                );
            }
        }

        case "datePicker": {
            const readOnlyValue = instance
                ? moment(instance).format("YYYY-M-D") //this format matches the DatePicker's readout
                : "";

            return disabled ? (
                <TextInput value={readOnlyValue} disabled />
            ) : (
                <DatePicker alignment="right" onChange={(value) => onChange(value)} value={instance} />
            );
        }
        case "dateRange": {
            return (
                <LazyDateRange
                    onStartChange={(date: string) => {
                        onChange({ ...(instance ?? {}), start: date });
                    }}
                    onEndChange={(date: string) => {
                        onChange({ ...(instance ?? {}), end: date });
                    }}
                    start={instance?.start}
                    end={instance?.end}
                    datePickerDropdownClassName={css({
                        zIndex: zIndex,
                        // FIXME: We should come up with better solution at some point, so this is determined automatically
                        // here is the ticket created for it: https://higherlogic.atlassian.net/browse/VNLA-4549
                        ...(dateRangeDirection !== "below" && { top: -350 }), //render above or below the input
                    })}
                />
            );
        }
        case "checkBox": {
            return (
                <Checkbox
                    label={control.label}
                    onChange={(event) => onChange(event.target.checked)}
                    disabled={disabled}
                    checked={instance}
                />
            );
        }
        case "dropDown": {
            const options = createOptionsFromRecord(control.choices.staticOptions);
            const currentValue = options.find((opt) => `${opt.value}` === `${instance}`);
            return (
                <>
                    <SelectOne
                        label={null}
                        disabled={disabled}
                        value={currentValue ?? null}
                        onChange={(option) => {
                            onChange(option?.value);
                        }}
                        options={options}
                    />
                    {!!required && (
                        <input
                            tabIndex={-1}
                            autoComplete="off"
                            style={{ opacity: 0, height: 0, width: "100%", display: "block" }}
                            value={currentValue ? currentValue.value : ""}
                            required={required}
                            onChange={() => {}}
                        />
                    )}
                </>
            );
        }
        case "tokens": {
            const options = createOptionsFromRecord(control.choices.staticOptions);

            const currentValue: IComboBoxOption[] = Object.values(instance ?? {}).map((value: string | number) => {
                return {
                    value,
                    label: options.find(({ value: optionValue }) => optionValue === value)?.label ?? `${value}`,
                };
            });

            return (
                <>
                    <Tokens
                        label={null}
                        disabled={disabled}
                        value={currentValue}
                        onChange={(options) => onChange(options.map(({ value }) => value))}
                        options={options}
                        inModal={inModal}
                    />
                    {!!required && (
                        <input
                            tabIndex={-1}
                            autoComplete="off"
                            style={{ opacity: 0, height: 0, width: "100%", display: "block" }}
                            value={currentValue ? currentValue.join(",") : ""}
                            required={required}
                            onChange={() => {}}
                        />
                    )}
                </>
            );
        }
        case "custom": {
            return (
                <control.component
                    {...(props as IControlProps<ICustomControl>)}
                    value={instance ?? props.schema.default}
                    onChange={onChange}
                >
                    {control.componentProps?.children}
                </control.component>
            );
        }
        default:
            return <div>{(control as any).inputType} is not supported</div>;
    }
}

export function FormControlGroup(props: React.PropsWithChildren<IControlGroupProps>) {
    const { children, controls, validation, schema } = props;
    const { inputType, label, legend, description } = controls[0];

    const errors =
        validation?.errors
            ?.filter((error) => error.instanceLocation === `#${props.pathString}`)
            .map((e) => {
                return {
                    message: e.error!,
                    field: `${props.path[0]!}`,
                };
            }) ?? [];

    return (
        <InputBlock
            label={inputType !== "checkBox" ? label : undefined}
            labelNote={inputType !== "checkBox" ? description : undefined}
            legend={legend}
            icon={<ProfileFieldVisibilityIcon visibility={schema.visibility} />}
            errors={errors}
            extendErrorMessage
        >
            {children}
        </InputBlock>
    );
}
