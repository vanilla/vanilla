/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { InputSize } from "@vanilla/ui/src/types";
import { ErrorObject } from "ajv/dist/core";
import { SomeJSONSchema } from "ajv/dist/types/json-schema";
import React from "react";

export type Path = Array<string | number>;

export interface ICommonControl {
    label?: string;
    labelType?: string;
    legend?: string;
    tooltip?: string;
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

interface IDropdownControl extends ICommonControl {
    inputType: "dropDown";
    choices: IChoices;
    multiple?: boolean;
    helperText?: string;
}

interface IRadioControl extends ICommonControl {
    inputType: "radio";
    choices: IChoices;
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
    modalContent: T;
}

export interface ICustomControl<
    P extends React.ComponentType<
        React.PropsWithChildren<{ value: any; onChange: (val: any) => void }>
    > = React.ComponentType<React.PropsWithChildren<{ value: any; onChange: (val: any) => void }>>,
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
    | ICodeBoxControl
    | ITabsControl
    | IColorControl
    | IUploadControl
    | IDatePickerControl
    | IDateRangeControl
    | IDragAndDropControl
    | IEmptyControl
    | IModalControl
    | ICustomControl;

export type IFormControlType = IFormControl["inputType"];

export type JsonSchema = Partial<SomeJSONSchema>;

export type Condition = { field: string; disable?: boolean } & JsonSchema;

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
    validation?: IValidationResult;
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
    title: string;
}

export interface IControlGroupProps extends IBaseSchemaFormProps {
    controls: IFormControl[];
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
}

export interface IValidationResult {
    isValid: boolean;
    errors?: ErrorObject[] | null;
}

export interface ISchemaRenderProps {
    FormControlGroup?: React.ComponentType<IControlGroupProps>;
    Form?: React.ComponentType<IFormProps>;
    FormSection?: React.ComponentType<ISectionProps>;
    FormTabs?: React.ComponentType<ITabsProps>;
    FormControl?: React.ComponentType<IControlProps>;
    FormGroupWrapper?: React.ComponentType<React.PropsWithChildren<{ groupName?: string; header?: string }>>;
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
