/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IFieldError } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import type { Select } from "@vanilla/json-schema-forms";

export interface INestedSelectProps extends Omit<Select.SelectConfig, "multiple"> {
    inputValue?: string;
    autoFocus?: boolean;
    disabled?: boolean;
    compact?: boolean;
    inline?: boolean;
    errors?: IFieldError[];
    placeholder?: string;
    prefix?: string;
    name?: string;
    label?: string;
    labelNote?: string;
    noteAfterInput?: string;
    id?: string;
    labelID?: string;
    inputID?: string;
    required?: boolean;
    ariaLabel?: string;
    ariaDescribedBy?: string;
    maxHeight?: number;
    tabIndex?: number;
    onSearch?: (inputValue?: string) => void;
    onInputChange?: (inputValue?: string) => void;
    classes?: {
        root?: string;
        input?: string;
        label?: string;
        inputContainer?: string;
    };
    createable?: boolean;
    createableLabel?: string;
    /** Function to determine whether a given option is user-created. Useful if the form has initial 'created' values */
    checkIsOptionUserCreated?: (value: RecordID) => boolean;
    /** List of values that should be looked up on mount */
    /** Get the text input from this component */
    onInputValueChange?: (inputValue?: string) => void;
    /** Force options fetched options to always be available to select */
    withOptionCache?: boolean;

    // Values
    multiple?: boolean | undefined;
    initialValues?: RecordID | RecordID[];
    value?: RecordID | RecordID[];
    defaultValue?: RecordID | RecordID[];
    onChange: (value?: RecordID | RecordID[], data?: any) => void;
}

export interface INestedSelectOptionProps extends Omit<Select.Option, "children" | "parent"> {
    id?: string;
    depth?: number;
    doNotIndent?: boolean;
    isHeader?: boolean;
    group?: string;
    tooltip?: string;
    onClick?: (value: RecordID) => void;
    isNested?: boolean;
    isSelected?: boolean;
    classes?: any;
    searchQuery?: string;
    highlighted?: boolean;
    onHover?: () => void;
    createableLabel?: string;
}

export interface INestedOptionsState {
    options: INestedSelectOptionProps[];
    optionsByValue: {
        [value: string]: INestedSelectOptionProps;
    };
    selectedOptions: Select.Option[];
    optionsByGroup: {
        [group: string]: INestedSelectOptionProps[];
    };
}
