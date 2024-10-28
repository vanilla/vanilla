/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export namespace Select {
    type RecordID = string | number;

    export interface Option {
        // If value is undefined, it is assumed to be a header
        // If you need an empty value, use an empty string
        value?: RecordID;
        label: string;
        extraLabel?: string;
        data?: any;
        children?: Option[];
    }

    export interface LookupApi {
        // URL for searching by labelKey
        searchUrl: string;
        // URL for a single record by valueKey
        singleUrl: string | null;
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
        initialOptions?: Option[] | undefined;
        // Method to transform it beyond the basic setup
        // Use this method to create a nested options list
        processOptions?: (options: Option[]) => Option[];
    }

    export interface SelectConfig {
        options?: Select.Option[];
        optionsLookup?: Select.LookupApi;
        isClearable?: boolean;
        multiple?: boolean;
        createable?: boolean;
    }
}
