/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { Optionalize } from "@library/@types/utils";
import { ISearchOptionData } from "@library/features/search/SearchOption";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { formatUrl, getMeta } from "@library/utility/appUtils";

const defaultOptionProvider: ISearchOptionProvider = {
    supportsAutoComplete: false,
    autocomplete: () => Promise.resolve([]),
    makeSearchUrl: (query) => formatUrl(`/search?search=${query}`, true),
};

const SearchContext = React.createContext<IWithSearchProps>({
    searchOptionProvider: defaultOptionProvider,
    externalSearchQuery: undefined,
});
export default SearchContext;

export function SearchContextProvider({ children }) {
    return (
        <SearchContext.Provider
            value={{
                searchOptionProvider: SearchContextProvider.optionProvider,
                externalSearchQuery: getMeta("search.externalSearchQuery", false), // check if we have external search query, no autocomplete and pointing into external search in this case
            }}
        >
            {children}
        </SearchContext.Provider>
    );
}

SearchContextProvider.optionProvider = defaultOptionProvider as ISearchOptionProvider;
SearchContextProvider.setOptionProvider = (provider: ISearchOptionProvider) => {
    SearchContextProvider.optionProvider = provider;
};

export interface ISearchOptionProvider {
    supportsAutoComplete?: boolean;
    autocomplete(query: string, options?: Record<string, any>): Promise<Array<IComboBoxOption<ISearchOptionData>>>;
    makeSearchUrl(query: string, options?: Record<string, any>, externalSearchQuery?: string): string;
}

export interface IWithSearchProps {
    searchOptionProvider: ISearchOptionProvider;
    externalSearchQuery?: string;
}

export function useSearch() {
    return useContext(SearchContext);
}

/**
 * HOC to inject API context
 *
 * @param WrappedComponent - The component to wrap
 */
export function withSearch<T extends IWithSearchProps = IWithSearchProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithDevice extends React.Component<Optionalize<T, IWithSearchProps>> {
        public static displayName = `withSearch(${displayName})`;
        public render() {
            return (
                <SearchContext.Consumer>
                    {(context) => {
                        // https://github.com/Microsoft/TypeScript/issues/28938
                        return <WrappedComponent {...context} {...(this.props as T)} />;
                    }}
                </SearchContext.Consumer>
            );
        }
    }
    return ComponentWithDevice;
}
