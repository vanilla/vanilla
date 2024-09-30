/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IFieldError } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";

// Either options or optionsLookup is required, but both should not be defined
export type NestedSelectOptions = { options: INestedSelectOption[] } | { optionsLookup: INestedLookupApi };

export interface INestedSelectProps {
    value?: RecordID | RecordID[];
    defaultValue?: RecordID | RecordID[];
    inputValue?: string;
    options?: INestedSelectOption[];
    optionsLookup?: INestedLookupApi;
    multiple?: boolean;
    autoFocus?: boolean;
    disabled?: boolean;
    isClearable?: boolean;
    compact?: boolean;
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
    onChange: (value?: RecordID | RecordID[], data?: any) => void;
    onSearch?: (inputValue?: string) => void;
    onInputChange?: (inputValue?: string) => void;
    classes?: Record<string, string>;
}

export interface INestedSelectOption {
    // If value is undefined, it is assumed to be a header
    // If you need an empty value, use an empty string
    value?: RecordID;
    label: string;
    extraLabel?: string;
    data?: any;
    children?: INestedSelectOption[];
}

export interface INestedSelectOptionProps extends Omit<INestedSelectOption, "children" | "parent"> {
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
}

export interface INestedLookupApi {
    // URL for searching by labelKey
    searchUrl: string;
    // URL for a single record by valueKey
    singleUrl: string;
    // URL for getting the default list of options if different from search
    defaultListUrl?: string;
    // The property that will display the option label
    // If value key is not defined, then this will also be the value
    labelKey: string;
    // The property that will act as the options unique value
    valueKey?: string;
    // The property that display additional label information
    extraLabelKey?: string;
    // The property that has the records to use for the options list
    resultsKey?: string;
    // Values that should not be included in the options
    excludeLookups?: RecordID[];
    // Static options to display initially
    initialOptions?: INestedSelectOption[] | undefined;
    // Method to transform it beyond the basic setup
    // Use this method to create a nested options list
    processOptions?: (options: INestedSelectOption[]) => INestedSelectOption[];
}

export interface INestedOptionsState {
    options: INestedSelectOptionProps[];
    optionsByValue: {
        [value: string]: INestedSelectOptionProps;
    };
    optionsByGroup: {
        [group: string]: INestedSelectOptionProps[];
    };
}
