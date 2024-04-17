/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { IControlGroupProps, IControlProps, ICustomControl, IRichEditorControl } from "@vanilla/json-schema-forms";
import InputBlock from "@library/forms/InputBlock";
import { TextInput } from "@library/forms/TextInput";
import DatePicker from "@library/forms/DatePicker";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne from "@library/forms/select/SelectOne";
import InputTextBlock from "@library/forms/InputTextBlock";
import Checkbox from "@library/forms/Checkbox";
import { Tokens } from "@library/forms/select/Tokens";
import moment from "moment";
import LazyDateRange from "@library/forms/LazyDateRange";
import { useStackingContext } from "@vanilla/react-utils";
import { css } from "@emotion/css";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import RadioButton from "@library/forms/RadioButton";
import { useUniqueID } from "@library/utility/idUtils";
import { RadioGroupContext } from "@library/forms/RadioGroupContext";
import { useIsInModal } from "@library/modal/Modal.context";
import { AutoComplete } from "@vanilla/ui";

const createOptionsFromRecord = (options?: Record<string, React.ReactNode>): IComboBoxOption[] => {
    return options
        ? Object.entries(options).map(([value, label]) => ({
              value,
              // Cast needed because technically it can be a react node. This tends to work out fine in practice.
              // Lots of type cleanup will be needed if the IComboBoxOption actually starts supporting `React.ReactNode`.
              label: label as any as string,
          }))
        : [];
};

export function FormControlWithNewDropdown(props: IControlProps) {
    return <FormControl {...props} useNewDropdown />;
}

export function FormControl(props: IControlProps & { useNewDropdown?: boolean }) {
    const { disabled, onChange, onBlur, control, instance, schema, required, dateRangeDirection = "above" } = props;

    const isInModal = useIsInModal();

    const inputName = useUniqueID("input");

    const value = instance;

    const { zIndex } = useStackingContext();

    switch (control.inputType) {
        case "textBox": {
            if (control.type === "textarea") {
                return (
                    <InputTextBlock
                        inputProps={{
                            multiline: true,
                            disabled: disabled,
                            value: value ?? "",
                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => onChange(event.target.value),
                            onBlur,
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
                        value={value}
                        min={control.min}
                        max={control.max}
                        minLength={control.minLength}
                        maxLength={control.maxLength}
                        type={control.type}
                        disabled={disabled}
                        required={required}
                        onBlur={onBlur}
                        onChange={(event) => {
                            let newValue: string | number | undefined =
                                event.target[control.type === "number" ? "valueAsNumber" : "value"];
                            if (control.type === "number" && Number.isNaN(newValue)) {
                                newValue = undefined;
                            }
                            onChange(newValue);
                        }}
                    />
                );
            }
        }

        case "datePicker": {
            const readOnlyValue = value
                ? moment(value).format("YYYY-MM-DD") //this format matches the DatePicker's readout
                : "";

            return disabled ? (
                <TextInput value={readOnlyValue} disabled onBlur={onBlur} />
            ) : (
                <DatePicker
                    alignment="right"
                    onChange={(value) => onChange(value)}
                    value={value}
                    required={required}
                    onBlur={onBlur}
                    datePickerDropdownClassName={css({
                        zIndex: zIndex,
                        // FIXME: We should come up with better solution at some point, so this is determined automatically
                        // here is the ticket created for it: https://higherlogic.atlassian.net/browse/VNLA-4549
                        ...(dateRangeDirection !== "below" && { top: -350 }), //render above or below the input
                    })}
                    min={control.min}
                    max={control.max}
                />
            );
        }

        case "dateRange": {
            return (
                <LazyDateRange
                    onStartChange={(date: string) => {
                        onChange({ ...(value ?? {}), start: date });
                    }}
                    onEndChange={(date: string) => {
                        onChange({ ...(value ?? {}), end: date });
                    }}
                    start={value?.start}
                    end={value?.end}
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
                    tooltip={control.tooltip}
                    tooltipIcon={control.tooltipIcon}
                    label={control.label}
                    onChange={(event) => onChange(event.target.checked)}
                    onBlur={onBlur}
                    disabled={disabled}
                    checked={value}
                />
            );
        }
        case "radio":
            return (
                <RadioGroupContext.Provider value={{ value, onChange }}>
                    {Object.entries(control.choices.staticOptions ?? []).map(
                        ([optionValue, label]: [string, string]) => (
                            <RadioButton
                                disabled={disabled}
                                name={inputName}
                                key={optionValue}
                                label={label}
                                value={optionValue}
                                tooltip={
                                    control.tooltipsPerOption && control.tooltipsPerOption[optionValue]
                                        ? control.tooltipsPerOption[optionValue]
                                        : undefined
                                }
                            />
                        ),
                    )}
                </RadioGroupContext.Provider>
            );
        case "dropDown": {
            const options = createOptionsFromRecord(control.choices.staticOptions);
            const currentValue = options.find((opt) => `${opt.value}` === `${value}`);
            return (
                <>
                    {props.useNewDropdown ? (
                        <AutoComplete
                            options={options}
                            value={value}
                            onBlur={onBlur}
                            clear={!required}
                            onChange={(value) => {
                                onChange(value);
                            }}
                            disabled={disabled}
                        />
                    ) : (
                        <SelectOne
                            label={null}
                            disabled={disabled}
                            value={currentValue ?? null}
                            onChange={(option) => {
                                onChange(option?.value);
                            }}
                            onBlur={onBlur}
                            options={options}
                            isClearable={!required}
                        />
                    )}
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

            const currentValue: IComboBoxOption[] = Object.values(value ?? {}).map((value: string | number) => {
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
                        onBlur={onBlur}
                        options={options}
                        inModal={isInModal}
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
        case "richeditor": {
            return (
                <VanillaEditor
                    showConversionNotice={!!control.initialFormat && control.initialFormat !== "rich2"}
                    onChange={(val) => {
                        onChange(JSON.stringify(val));
                    }}
                    onBlur={onBlur}
                    initialContent={value}
                    initialFormat={control.initialFormat}
                />
            );
        }
        case "custom": {
            return (
                <control.component
                    {...(props as IControlProps<ICustomControl>)}
                    value={value}
                    onChange={onChange}
                    onBlur={onBlur}
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
    const { children, controls, validation, required } = props;

    const { inputType, label, legend, description, tooltip, tooltipIcon } = controls[0];

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
            required={required}
            label={inputType !== "checkBox" ? label : undefined}
            labelNote={inputType !== "checkBox" ? description : undefined}
            legend={legend}
            tooltip={tooltip}
            tooltipIcon={tooltipIcon}
            errors={errors}
            extendErrorMessage
        >
            {children}
        </InputBlock>
    );
}
