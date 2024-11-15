/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IFieldError } from "@vanilla/json-schema-forms";
import { RecordID } from "@vanilla/utils";
import type { Select } from "@vanilla/json-schema-forms";

export interface INestedSelectProps extends Select.SelectConfig {
    value?: RecordID | RecordID[];
    defaultValue?: RecordID | RecordID[];
    inputValue?: string;
    autoFocus?: boolean;
    disabled?: boolean;
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
    createable?: boolean;
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
