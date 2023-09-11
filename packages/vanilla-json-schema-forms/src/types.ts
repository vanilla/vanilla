/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Schema, ValidationResult, InstanceType } from "@cfworker/json-schema";
import { InputSize } from "@vanilla/ui/src/types";
import { RecordID } from "@vanilla/utils";
import React from "react";

export type Path = Array<string | number>;

export interface ICommonControl {
    label?: string;
    labelType?: string;
    legend?: string;
    tooltip?: string;
    default?: string;
    disabled?: boolean;
    disabledNote?: string;
    description?: string;
    placeholder?: string;
    conditions?: Condition[];
    fullSize?: boolean;
    errorPathString?: string;
    inputID?: string; //this one is non standard, for cases if we want to manipulate values of form elements
    inputAriaLabel?: string; // and this one is in case we don't want visible label, but still want the input be accessible
}

interface IChoices {
    staticOptions?: Record<string, string>;
    api?: {
        searchUrl: string;
        singleUrl: string;
        valueKey: string;
        labelKey: string;
    };
}

interface ICheckBoxControl extends ICommonControl {
    inputType: "checkBox" | "toggle";
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
}

interface IRadioControl extends ICommonControl {
    inputType: "radio";
    choices: IChoices;
    enum?: string[];
}

interface ITextBoxControl extends ICommonControl {
    inputType: "textBox";
    type?: "text" | "textarea" | "number" | "url" | "password";
}

interface ICodeBoxControl extends ICommonControl {
    inputType: "codeBox";
    language?: string;
    jsonSchemaUri?: string;
    boxHeightOverride?: number;
}

interface IRichEditorControl extends ICommonControl {
    inputType: "richeditor";
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
}

export interface IDateRangeControl extends ICommonControl {
    inputType: "dateRange";
}

export interface IDragAndDropControl extends ICommonControl {
    inputType: "dragAndDrop";
}

export interface IEmptyControl extends ICommonControl {
    inputType: "empty";
}
export interface IModalControl<T = ICommonControl> extends ICommonControl {
    inputType: "modal";
    modalTriggerLabel: string;
    modalContent: T;
}

export interface ICustomControl<
    P extends React.ComponentType<object> = React.ComponentType<
        React.PropsWithChildren<{ value?: any; onChange?: (val: any) => void }>
    >,
> extends ICommonControl {
    inputType: "custom";
    component: P;
    componentProps?: React.ComponentProps<P>;
}

export type IFormControl =
    | IDropdownControl
    | IRadioControl
    | ITokensControl
    | ITextBoxControl
    | ICheckBoxControl
    | IRichEditorControl
    | ICodeBoxControl
    | ITabsControl
    | IColorControl
    | IUploadControl
    | IDatePickerControl
    | IDateRangeControl
    | IDragAndDropControl
    | IEmptyControl
    | IModalControl
    | ICustomControl<any>;

export type JsonSchema<T = void> = JSONSchemaType<T>;

export const EMPTY_SCHEMA: JSONSchemaType = { type: "object", properties: {}, required: [] };

export interface Condition extends Partial<JsonSchema> {
    field?: string;
    disable?: boolean;
    ref?: string;
    type?: string;
    const?: RecordID | boolean;
}

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
    label: string;
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
    title?: string;
    description?: string;
}

export interface IControlGroupProps extends IBaseSchemaFormProps {
    controls: IFormControl[];
    required?: boolean;
}

export interface IControlProps<T = IFormControl> extends IBaseSchemaFormProps {
    control: T;
    required?: boolean;
    disabled?: boolean;
    onChange(instance: any): void;
    onBlur?(): void;
    size?: InputSize;
    autocompleteClassName?: string;
    /** If this form is rendered within a modal, allows for options boxes to be rendered outside */
    inModal?: boolean;
    /** This controls the direction for DateRange option boxes (DatePicker in the end) */
    dateRangeDirection?: "above" | "below";
}

export interface IValidationResult extends ValidationResult {}

export interface ISchemaRenderProps {
    FormControlGroup?: React.ComponentType<IControlGroupProps>;
    Form?: React.ComponentType<IFormProps>;
    FormSection?: React.ComponentType<ISectionProps>;
    FormTabs?: React.ComponentType<ITabsProps>;
    FormControl?: React.ComponentType<IControlProps>;
    FormGroupWrapper?: React.ComponentType<{ groupName?: string; header?: string; description?: string }>;
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
