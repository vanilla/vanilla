/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Schema, ValidationResult, InstanceType } from "@cfworker/json-schema";
import React from "react";
import { type IconType } from "@vanilla/icons";
import { Select } from ".";
import { INestedSelectProps } from "@library/forms/nestedSelect";

export type Path = Array<string | number>;

export interface ICommonControl {
    label?: string | React.ReactNode;
    labelType?: "none" | "wide" | "standard" | "vertical" | "justified";
    legend?: string;
    default?: string;
    disabled?: boolean;
    tooltip?: string | React.ReactNode;
    tooltipIcon?: IconType;
    description?: string | null | React.ReactNode;
    placeholder?: string;
    conditions?: Condition[];
    fullSize?: boolean;
    errorPathString?: string;
    inputID?: string; //this one is non standard, for cases if we want to manipulate values of form elements
    inputAriaLabel?: string; // and this one is in case we don't want visible label, but still want the input be accessible
    labelClassname?: string;
    noBorder?: boolean;
    isNested?: boolean;
}

export interface IChoices {
    staticOptions?: Record<string, React.ReactNode>;
    api?: {
        searchUrl: string;
        singleUrl: string;
        valueKey: string;
        labelKey: string;
        extraLabelKey?: string;
        excludeLookups?: string[];
    };
}

export interface IStaticChoices extends IChoices {
    api: undefined;
}

export interface ICheckBoxControl extends ICommonControl {
    inputType: "checkBox" | "toggle";
    labelBold?: boolean;
    checkPosition?: "left" | "right";
}

export interface ITokensControl extends ICommonControl {
    inputType: "tokens";
    choices: IChoices;
}

export interface IDropdownControl extends ICommonControl {
    inputType: "dropDown";
    choices: IChoices;
    multiple?: boolean;
    helperText?: string;
    type?: string;
    openDirection?: "top" | "bottom" | "auto";
}

export interface ISelectControl extends Select.SelectConfig, ICommonControl {
    inputType: "select";
    createableLabel?: string;
    choices?: Select.SelectConfig["options"];
    checkIsOptionUserCreated?: INestedSelectProps["checkIsOptionUserCreated"];
}

export interface IRadioControl extends ICommonControl {
    inputType: "radio";
    choices: IChoices;
    enum?: string[];
    // for radio buttons, this is an object of value - tooltip pairs
    tooltipsPerOption?: Record<string, string>;
    // note to display with each option
    notesPerOption?: Record<string, string>;
}

export interface ICheckBoxGroupControl extends ICommonControl {
    inputType: "checkBoxGroup";
    choices: IChoices;
    // for checks buttons, this is an object of value - tooltip pairs
    tooltipsPerOption?: Record<string, string>;
    // note to display with each option
    notesPerOption?: Record<string, string>;
}

export interface ITextBoxControl extends ICommonControl {
    inputType: "textBox";
    type?: "text" | "textarea" | "url" | "password" | "time" | "currency" | "ratio";

    minLength?: React.InputHTMLAttributes<HTMLInputElement>["minLength"];
    maxLength?: React.InputHTMLAttributes<HTMLInputElement>["maxLength"];
    pattern?: React.InputHTMLAttributes<HTMLInputElement>["pattern"];
}

interface INumberControl extends Omit<ITextBoxControl, "type"> {
    type: "number";
    min?: React.InputHTMLAttributes<HTMLInputElement>["min"];
    max?: React.InputHTMLAttributes<HTMLInputElement>["max"];
}

interface IRatioControl extends Omit<INumberControl, "type"> {
    type: "ratio";
}

interface ICurrencyControl extends Omit<INumberControl, "type"> {
    type: "currency";
}

interface ICodeBoxControl extends ICommonControl {
    inputType: "codeBox";
    language?: string;
    jsonSchemaUri?: string;
    boxHeightOverride?: number;
}

export interface IRichEditorControl extends ICommonControl {
    inputType: "richeditor";
    initialFormat?: string;
}

export interface ITabsControl extends ICommonControl {
    inputType: "tabs";
    property: string;
    choices: IChoices;
}

export interface IColorControl extends ICommonControl {
    inputType: "color";
    type?: string;
    defaultBackground?: string;
}

export interface IUploadControl extends ICommonControl {
    inputType: "upload";
}

export interface IDatePickerControl extends ICommonControl {
    inputType: "datePicker";
    min?: React.InputHTMLAttributes<HTMLInputElement>["min"];
    max?: React.InputHTMLAttributes<HTMLInputElement>["max"];
}

export interface IPickerOption {
    label: string | React.ReactNode;
    value: string;
    description?: React.ReactNode;
    tooltip?: string;
}

export interface IRadioPickerControl extends ICommonControl {
    inputType: "radioPicker";
    options: IPickerOption[];
    pickerTitle?: string;
}

export interface IDurationPickerControl extends ICommonControl {
    inputType: "timeDuration";
    supportedUnits?: string[];
}

export interface IDateRangeControl extends ICommonControl {
    inputType: "dateRange";
    dateRangeDirection?: "above" | "below";
}

export type IDragAndDropControl = ICommonControl & {
    inputType: "dragAndDrop";
    itemSchema: JsonSchema;
    allowAdd?: boolean; // Allow adding new items
} & (
        | {
              asModal: true;
              modalTitle: string;
              modalSubmitLabel: string;
          }
        | {
              asModal?: false;
          }
    );

export interface IEmptyControl extends ICommonControl {
    inputType: "empty";
}
export interface IModalControl<T = ICommonControl> extends ICommonControl {
    inputType: "modal";
    modalTriggerLabel: string;
    modalContent: T;
}

export type ISubheadingControl = ICommonControl & {
    inputType: "subheading";
    label: React.ReactNode;
    actions?: string;
};

export type IStaticTextControl = ICommonControl & {
    inputType: "staticText";
    label: React.ReactNode;
    actions?: string;
};

export interface ICustomControl<
    P extends React.ComponentType<object> = React.ComponentType<
        React.PropsWithChildren<{ value?: any; onChange?: (val: any) => void; errors: IFieldError[] }>
    >,
> extends ICommonControl {
    inputType: "custom";
    component: P | string;
    componentProps?: React.ComponentProps<P>;
}

export type IFormControl =
    | IDropdownControl
    | IRadioControl
    | ITokensControl
    | ITextBoxControl
    | INumberControl
    | IRatioControl
    | IRadioPickerControl
    | ICurrencyControl
    | ICheckBoxControl
    | ICheckBoxGroupControl
    | IRichEditorControl
    | ICodeBoxControl
    | ITabsControl
    | IColorControl
    | IUploadControl
    | IDatePickerControl
    | IDateRangeControl
    | IDurationPickerControl
    | IDragAndDropControl
    | IEmptyControl
    | IModalControl
    | ISubheadingControl
    | IStaticTextControl
    | ICustomControl<any>
    | ISelectControl;

export type JsonSchema<T = void> = JSONSchemaType<T>;

export const EMPTY_SCHEMA: JSONSchemaType = { type: "object", properties: {}, required: [] };

export interface Condition extends Partial<JsonSchema> {
    field?: string;
    disable?: boolean;
    ref?: string;
    type?: string;
    const?: any;
    enum?: any[];
    invert?: boolean;
    [key: string]: any;
}

export type CustomConditionEvaluator = (condition: Condition, rootInstance: any) => boolean | null;

export interface ISchemaTab {
    id: string;
    label: string;
    isDefault?: boolean;
}

export interface IBaseSchemaFormProps {
    path: Array<string | number>;
    pathString: string;
    errors: IFieldError[];
    schema: JsonSchema;
    rootSchema: JsonSchema;
    instance: any;
    rootInstance: any;
    validation?: ValidationResult;
}

export interface IForm {
    url: string;
    searchParams: Record<string, string>;
    submitButtonText?: string;
}

export interface IFormTab {
    tabID: string;
    label: React.ReactNode;
    contents: JSX.Element;
}

export interface ITabsProps extends IBaseSchemaFormProps {
    selectedTabID: string;
    onSelectTab(tabID: string): void;
    tabs: IFormTab[];
}

export interface IFormProps extends IBaseSchemaFormProps {
    form: IForm;
}

export interface ISectionProps extends IBaseSchemaFormProps {
    title?: React.ReactNode;
    description?: React.ReactNode;
}

export interface IControlGroupProps extends IBaseSchemaFormProps {
    controls: IFormControl[];
    required?: boolean;
}

export interface ICustomControlProps extends IControlProps<ICustomControl> {
    value: any;
    onChange(instance: any): void;
    errors: IFieldError[];
}

export interface IControlProps<T = IFormControl> extends IBaseSchemaFormProps {
    control: T;
    required?: boolean;
    disabled?: boolean;
    onChange(instance: any): void;
    onBlur?(): void;
    size?: "small" | "default";
    autocompleteClassName?: string;
    /** This controls the direction for DateRange option boxes (DatePicker in the end) */
    dateRangeDirection?: "above" | "below";
}

export interface IValidationResult extends ValidationResult {}

export interface ISchemaRenderProps {
    FormControlGroup?: React.ComponentType<IControlGroupProps>;
    Form?: React.ComponentType<IFormProps>;
    FormSection?: React.ComponentType<React.PropsWithChildren<ISectionProps>>;
    FormTabs?: React.ComponentType<ITabsProps>;
    FormControl?: React.ComponentType<IControlProps>;
    FormGroupWrapper?: React.ComponentType<
        React.PropsWithChildren<{
            groupName?: string;
            header?: React.ReactNode;
            description?: React.ReactNode;
            rootInstance?: any;
        }>
    >;
}

export interface IPtrReference {
    path: Array<string | number>;
    ref: Path;
}

export interface IFieldError {
    /** translated message */
    message: string;
    /** translation code */
    code?: string;
    field: string;
    /** HTTP status */
    status?: number;
    /** If we are nested this the path we are nested in.*/
    path?: string;
}

export interface SchemaErrorMessage {
    keyword: string;
    message: string;
}

export interface PartialSchemaDefinition<T = any> extends Omit<Schema, "properties"> {
    type?: T | InstanceType | InstanceType[];
    nullable?: boolean;
    "x-control"?: ICommonControl | IFormControl | IFormControl[];
    errorMessage?: string | SchemaErrorMessage[];
}

export interface JSONSchemaType<T = void> extends Omit<Schema, "properties"> {
    properties: T extends object
        ? Partial<Record<keyof T, PartialSchemaDefinition<T>>>
        : Record<string, PartialSchemaDefinition>;
}
