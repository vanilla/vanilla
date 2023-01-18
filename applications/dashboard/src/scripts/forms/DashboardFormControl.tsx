/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardCodeEditor } from "@dashboard/forms/DashboardCodeEditor";
import { DashboardCustomComponent } from "@dashboard/forms/DashboardCustomComponent";
import { DashboardDatePicker } from "@dashboard/forms/DashboardDatePicker";
import { DashboardColorPicker } from "@dashboard/forms/DashboardFormColorPicker";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardPasswordInput } from "@dashboard/forms/DashboardPasswordInput";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import apiv2 from "@library/apiv2";
import ErrorMessages from "@library/forms/ErrorMessages";
import { FormTreeControl } from "@library/tree/FormTreeControl";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { IControlGroupProps, IControlProps } from "@vanilla/json-schema-forms";
import { AutoComplete, IFormGroupProps } from "@vanilla/ui";
import { AutoCompleteLookupOptions } from "@vanilla/ui/src/forms/autoComplete/AutoCompleteLookupOptions";
import isEmpty from "lodash/isEmpty";
import React from "react";

interface IControlOverride {
    /** This boolean controls if the associated component (in callback) should be rendered */
    condition: (props: IControlProps) => boolean;
    /** Expects a react component to be produced, will render when the defined condition is met */
    callback: (props: IControlProps) => JSX.Element;
}

/**
 * This is intended for use in the JsonSchemaForm component
 * TODO: We need to replace these inputs with vanilla-ui
 * Important: An exception will occur if this is used without DashboardFormControlGroup
 * @param props - The Control Props passed in from JSONSchema Form
 * @param controlOverrides - Array of one-off controls that should short circuit the returned control
 * @returns
 */
export function DashboardFormControl(props: IControlProps, controlOverrides?: IControlOverride[]) {
    const { control, required, disabled, instance, schema, onChange, onBlur, validation, size, autocompleteClassName } =
        props;
    const value = instance ?? schema.default;
    const inputName = useUniqueID("input");

    // If specific controls need to be overridden
    if (!isEmpty(controlOverrides)) {
        // Identify the specific control that matches the condition
        const control = controlOverrides?.find(({ condition }) => condition(props))?.callback(props);
        // Return that instead of the standard form controls set
        if (control) {
            return control;
        }
    }

    const fieldErrors = props.errors;
    switch (control.inputType) {
        case "textBox":
            const isMultiline = control.type === "textarea";
            const typeIsNumber = control.type === "number";
            const typeIsUrl = control.type === "url";
            const typeIsPassword = control.type === "password";
            const type = typeIsNumber ? "number" : typeIsUrl ? "url" : typeIsPassword ? "password" : "text";
            const inputProps = {
                value: value ?? "",
                required,
                disabled,
                onBlur,
                onChange: (event) => onChange(event.target.value),
                maxLength: schema.type === "string" ? schema.maxLength : undefined,
                type: !isMultiline ? type : undefined,
                placeholder: control.placeholder,
                multiline: isMultiline ? true : false,
                inputID: control.inputID,
                "aria-label": control.inputAriaLabel,
            };
            return typeIsPassword ? (
                <DashboardPasswordInput errors={fieldErrors} inputProps={inputProps} />
            ) : (
                <DashboardInput
                    errors={fieldErrors}
                    inputProps={inputProps}
                    multiLineProps={
                        isMultiline
                            ? {
                                  rows: 4,
                              }
                            : undefined
                    }
                />
            );

        case "codeBox":
            return (
                <DashboardCodeEditor
                    value={value}
                    onChange={onChange}
                    language={control.language || "text/html"}
                    jsonSchemaUri={control.jsonSchemaUri}
                    boxHeightOverride={control.boxHeightOverride}
                />
            );
        case "radio":
            return (
                <DashboardRadioGroup value={value} onChange={onChange}>
                    {Object.entries(control.choices.staticOptions ?? []).map(
                        ([optionValue, label]: [string, string]) => (
                            <DashboardRadioButton
                                disabled={props.disabled}
                                name={inputName}
                                key={optionValue}
                                label={label}
                                value={optionValue}
                            />
                        ),
                    )}
                </DashboardRadioGroup>
            );
        case "dropDown":
            const { api, staticOptions } = control.choices;
            const createOptions = () => {
                if (staticOptions) {
                    return Array.isArray(staticOptions)
                        ? staticOptions
                        : Object.entries(staticOptions).map(([value, label]) => ({
                              value,
                              label: String(label),
                          }));
                }
                return undefined;
            };
            return (
                <div className="input-wrap">
                    <AutoComplete
                        value={value}
                        clear={!required}
                        placeholder={control.placeholder}
                        onChange={(value) => {
                            onChange(value);
                        }}
                        onBlur={onBlur}
                        optionProvider={api ? <AutoCompleteLookupOptions api={apiv2} lookup={api} /> : undefined}
                        options={createOptions()}
                        size={size}
                        className={autocompleteClassName}
                        multiple={control.multiple}
                        required={required}
                        disabled={props.disabled}
                    />
                    {fieldErrors && <ErrorMessages errors={fieldErrors} />}

                    {control.helperText && <div className={dashboardClasses().helperText}>{control.helperText}</div>}
                </div>
            );
        case "checkBox":
            return (
                <div className="input-wrap">
                    <DashboardCheckBox
                        fullWidth
                        label={control.label ?? "Unlabeled"}
                        disabled={props.disabled}
                        checked={value ?? false}
                        onChange={onChange}
                        className={
                            control.labelType === DashboardLabelType.NONE ? dashboardClasses().noLeftPadding : undefined
                        }
                        disabledNote={control.disabledNote}
                    />
                </div>
            );

        case "toggle":
            return <DashboardToggle disabled={props.disabled} checked={value} onChange={onChange} />;
        case "dragAndDrop":
            return <FormTreeControl {...(props as any)} />;
        case "upload":
            return (
                <DashboardImageUploadGroup
                    value={value}
                    onChange={onChange}
                    label={control.label ?? t("Image Upload")}
                    description={control.description}
                    disabled={props.disabled}
                    tooltip={control.tooltip}
                />
            );
        case "color":
            return (
                <DashboardColorPicker
                    value={value}
                    onChange={onChange}
                    disabled={props.disabled}
                    placeholder={control.placeholder}
                    defaultBackground={control.defaultBackground}
                />
            );
        case "datePicker": {
            return (
                <DashboardDatePicker
                    value={value}
                    onChange={onChange}
                    disabled={props.disabled}
                    placeholder={control.placeholder}
                    inputAriaLabel={control.inputAriaLabel || control.label}
                />
            );
        }
        case "custom": {
            return <DashboardCustomComponent control={control} />;
        }
        case "empty":
            return <></>;
        default:
            return <div>{(control as any).inputType} is not supported</div>;
    }
}

/**
 * This is intended for use in the JsonSchemaForm component
 * @param props
 * @returns
 */
export function DashboardFormControlGroup(props: React.PropsWithChildren<IControlGroupProps> & IFormGroupProps) {
    const { children, controls } = props;
    const { label, description, fullSize, inputType, tooltip, labelType } = controls[0];
    if (fullSize || inputType === "upload") {
        return <>{children}</>;
    }
    return (
        <DashboardFormGroup
            label={label ?? ""}
            description={description}
            inputType={inputType}
            tooltip={tooltip}
            labelType={labelType as DashboardLabelType}
            inputID={controls[0].inputID}
        >
            {children}
        </DashboardFormGroup>
    );
}
