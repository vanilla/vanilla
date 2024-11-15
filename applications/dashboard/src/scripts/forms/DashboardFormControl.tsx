/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardAutoComplete } from "@dashboard/forms/DashboardAutoComplete";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardCodeEditor } from "@dashboard/forms/DashboardCodeEditor";
import DashboardCurrencyInput from "@dashboard/forms/DashboardCurrencyInput";
import { DashboardCustomComponent } from "@dashboard/forms/DashboardCustomComponent";
import { DashboardDatePicker } from "@dashboard/forms/DashboardDatePicker";
import { DashboardDurationPicker } from "@dashboard/forms/DashboardDurationPicker";
import { DashboardColorPicker } from "@dashboard/forms/DashboardFormColorPicker";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { DashboardPasswordInput } from "@dashboard/forms/DashboardPasswordInput";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardRadioPicker } from "@dashboard/forms/DashboardRadioPicker";
import DashboardRatioInput from "@dashboard/forms/DashboardRatioInput";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { css, cx } from "@emotion/css";
import apiv2 from "@library/apiv2";
import LazyDateRange from "@library/forms/LazyDateRange";
import { NestedSelect } from "@library/forms/nestedSelect";
import { FormTreeControl } from "@library/tree/FormTreeControl";
import { useUniqueID } from "@library/utility/idUtils";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { t } from "@vanilla/i18n";
import { ICommonControl, IControlProps, ICustomControl } from "@vanilla/json-schema-forms";
import { useStackingContext } from "@vanilla/react-utils";
import { AutoCompleteLookupOptions } from "@vanilla/ui/src/forms/autoComplete/AutoCompleteLookupOptions";
import isEmpty from "lodash-es/isEmpty";

interface IControlOverride<T = ICommonControl> {
    /** This boolean controls if the associated component (in callback) should be rendered */
    condition: (props: IControlProps<T>) => boolean;
    /** Expects a react component to be produced, will render when the defined condition is met */
    callback: (props: IControlProps<T>) => JSX.Element;
}

/**
 * This is intended for use in the JsonSchemaForm component
 * TODO: We need to replace these inputs with vanilla-ui
 * Important: An exception will occur if this is used without DashboardFormControlGroup
 * @param props - The Control Props passed in from JSONSchema Form
 * @param controlOverrides - Array of one-off controls that should short circuit the returned control
 * @returns
 */
// TODO: pass onBlur prop to all input components rendered by DashboardFormControl
export function DashboardFormControl(props: IControlProps, controlOverrides?: IControlOverride[]) {
    const {
        control,
        required,
        disabled,
        instance: value,
        schema,
        onChange,
        onBlur,
        autocompleteClassName,
        dateRangeDirection: propsDateRangeDirection = "above",
    } = props;

    const dateRangeDirection = props.control?.["dateRangeDirection"] ?? propsDateRangeDirection;

    const { zIndex } = useStackingContext();

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
            const controlType = control.type;

            const isMultiline = controlType === "textarea";
            const typeIsNumber = controlType === "number";
            const typeIsUrl = controlType === "url";
            const typeIsPassword = controlType === "password";
            const type = typeIsUrl ? "url" : typeIsPassword ? "password" : "text";
            const inputProps = {
                value: value ?? "",
                required,
                disabled,
                onBlur,
                minLength: schema.type === "string" ? control.minLength : undefined,
                maxLength: schema.type === "string" ? schema.maxLength : undefined,
                type: !isMultiline ? type : undefined,
                placeholder: control.placeholder,
                multiline: isMultiline ? true : false,
                className: isMultiline ? dashboardClasses().multiLineInput : undefined,
                inputID: control.inputID,
                "aria-label": control.inputAriaLabel,
                pattern: control.pattern,
                ...(typeIsNumber && {
                    min: schema.minimum ?? schema.min,
                    max: schema.maximum ?? schema.max,
                    step: schema.step,

                    // https://html.spec.whatwg.org/multipage/input.html#when-number-is-not-appropriate
                    inputmode: "numeric",
                    pattern: "[0-9]*",
                }),
            };
            if (controlType === "currency") {
                return <DashboardCurrencyInput {...inputProps} value={value} onChange={onChange} />;
            }
            if (controlType === "ratio") {
                return <DashboardRatioInput {...inputProps} value={value} onChange={onChange} />;
            }
            return typeIsPassword ? (
                <DashboardPasswordInput
                    errors={fieldErrors}
                    inputProps={inputProps}
                    onChange={onChange}
                    renderGeneratePasswordButton
                />
            ) : (
                <DashboardInput
                    errors={fieldErrors}
                    inputProps={{
                        ...inputProps,
                        onChange: (event) => {
                            const value = event.target.value;
                            onChange(value);
                        },
                    }}
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

        case "richeditor":
            // Force the Vanilla editor to mobile mode so that the floating toolbar stays within the editor, it otherwise floats offscreen in this view
            return (
                <DashboardInputWrap>
                    <VanillaEditor uploadEnabled={false} onChange={onChange} initialContent={value} isMobile />
                </DashboardInputWrap>
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
                                tooltip={
                                    control.tooltipsPerOption && control.tooltipsPerOption[optionValue]
                                        ? control.tooltipsPerOption[optionValue]
                                        : undefined
                                }
                                note={
                                    control.notesPerOption && control.notesPerOption[optionValue]
                                        ? control.notesPerOption[optionValue]
                                        : undefined
                                }
                            />
                        ),
                    )}
                </DashboardRadioGroup>
            );
        case "radioPicker":
            return (
                <DashboardRadioPicker
                    value={value}
                    onChange={onChange}
                    options={control.options}
                    pickerTitle={control.pickerTitle}
                />
            );
        case "select": {
            return (
                <DashboardInputWrap>
                    <NestedSelect
                        defaultValue={control.default}
                        multiple={control.multiple}
                        errors={fieldErrors}
                        value={value}
                        required={required}
                        disabled={props.disabled}
                        isClearable={control.isClearable ?? !required}
                        placeholder={control.placeholder}
                        onChange={onChange}
                        options={control.options}
                        optionsLookup={control.optionsLookup}
                        createable={control.createable}
                    />
                </DashboardInputWrap>
            );
        }
        case "dropDown":
        case "tokens":
            const multiple = control.inputType === "tokens" ? true : control.multiple;
            const helperText = control.inputType === "dropDown" ? control.helperText : undefined;

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
                <DashboardAutoComplete
                    errors={fieldErrors}
                    afterInput={helperText && <div className={dashboardClasses().helperText}>{helperText}</div>}
                    value={value}
                    clear={!required && !multiple}
                    placeholder={control.placeholder}
                    onChange={onChange}
                    onBlur={onBlur}
                    optionProvider={api ? <AutoCompleteLookupOptions api={apiv2} lookup={api} /> : undefined}
                    options={createOptions()}
                    className={cx(dashboardClasses().reachAutocompleteInput, autocompleteClassName)}
                    multiple={multiple}
                    required={required}
                    disabled={props.disabled}
                />
            );
        case "checkBox":
            const useStandardLabel = control.checkPosition === "right";
            return (
                <DashboardInputWrap>
                    <DashboardCheckBox
                        fullWidth
                        label={control.label ?? "Unlabeled"}
                        disabled={props.disabled}
                        checked={value ?? false}
                        onChange={onChange}
                        className={
                            control.labelType === DashboardLabelType.NONE ? dashboardClasses().noLeftPadding : undefined
                        }
                        tooltip={control.tooltip}
                        tooltipIcon={control.tooltipIcon}
                        description={useStandardLabel ? undefined : control.description}
                        name={inputName}
                        labelBold={control.labelBold}
                        hideLabel={useStandardLabel}
                    />
                </DashboardInputWrap>
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
                    inputAriaLabel={
                        control.inputAriaLabel || (typeof control.label === "string" ? control.label : undefined)
                    }
                />
            );
        }
        case "dateRange": {
            return (
                <DashboardInputWrap isVertical>
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
                </DashboardInputWrap>
            );
        }
        case "timeDuration": {
            return (
                <DashboardDurationPicker
                    value={value}
                    onChange={onChange}
                    errors={fieldErrors}
                    supportedUnits={control.supportedUnits}
                    disabled={props.disabled}
                />
            );
        }
        case "subheading": {
            return (
                <DashboardFormSubheading hasBackground={true} actions={control.actions}>
                    {control.label}
                </DashboardFormSubheading>
            );
        }
        case "custom": {
            return <DashboardCustomComponent {...(props as IControlProps<ICustomControl>)} />;
        }
        case "empty":
            return <></>;
        default:
            return <div>{(control as any).inputType} is not supported</div>;
    }
}
