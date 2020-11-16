/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext } from "react";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";

export const SEARCH_SCOPE_LOCAL = "site";
export const SEARCH_SCOPE_EVERYWHERE = "hub";

interface ISearchScope {
    optionsItems: ISelectBoxItem[];
    value?: ISelectBoxItem;
    onChange?: (value: ISelectBoxItem) => void;
    setValue?: (value: string) => void;
    compact?: boolean;
}

export interface ISearchScopeNoCompact extends Omit<ISearchScope, "compact"> {}

export const SearchScopeContext = React.createContext<ISearchScope>({
    optionsItems: [],
});

export function useSearchScope() {
    return useContext(SearchScopeContext);
}

const emptyItems = [];

export function EmptySearchScopeProvider(props: { children: React.ReactNode }) {
    return (
        <SearchScopeContext.Provider
            value={{
                optionsItems: emptyItems,
            }}
        >
            {props.children}
        </SearchScopeContext.Provider>
    );
}
