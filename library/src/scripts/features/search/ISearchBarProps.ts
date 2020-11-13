/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IOptionalComponentID } from "@library/utility/idUtils";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ISearchBarOverwrites } from "@library/features/search/searchBarStyles";
import { ISearchScopeNoCompact } from "./SearchScopeContext";
import { RecordID } from "@vanilla/utils";

export interface ISearchBarProps extends IOptionalComponentID {
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options?: any[];
    loadOptions?: (inputValue: string, options?: { [key: string]: any }) => Promise<any>;
    value: string;
    onChange: (value: string) => void;
    noHeading?: boolean;
    title?: string;
    titleAsComponent?: React.ReactNode;
    isLoading?: boolean;
    onSearch: () => void;
    optionComponent?: React.ComponentType<any>;
    getRef?: any;
    buttonClassName?: string;
    buttonDropDownClassName?: string;
    buttonLoaderClassName?: string;
    hideSearchButton?: boolean;
    triggerSearchOnClear?: boolean;
    resultsRef?: React.RefObject<HTMLDivElement>;
    handleOnKeyDown?: (event: React.KeyboardEvent) => void;
    onOpenSuggestions?: () => void;
    onCloseSuggestions?: () => void;
    buttonText?: string;
    disableAutocomplete?: boolean;
    clearButtonClass?: string;
    contentClass?: string;
    buttonBaseClass?: ButtonTypes;
    valueContainerClasses?: string;
    iconContainerClasses?: string;
    resultsAsModalClasses?: string;
    forceMenuOpen?: boolean;
    forcedOptions?: any[];
    needsPageTitle?: boolean;
    scope?: ISearchScopeNoCompact;
    overwriteSearchBar?: ISearchBarOverwrites;
}

export interface IComboBoxOption<T = any> {
    value: RecordID;
    label: string;
    data?: T;
}
