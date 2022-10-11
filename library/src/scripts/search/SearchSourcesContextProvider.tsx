/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ISearchSource } from "@library/search/searchTypes";
import React, { ReactNode, useContext, useEffect, useState } from "react";
import { SearchService } from "@library/search/SearchService";

interface ISearchSourcesContext {
    sources: ISearchSource[];
    currentSource: ISearchSource;
    setCurrentSource: (key: ISearchSource["key"]) => void;
}

export const SearchSourcesContext = React.createContext<ISearchSourcesContext>({
    sources: SearchService.pluggableSources,
    currentSource: SearchService.pluggableSources[0]!,
    setCurrentSource: (_key) => null,
});

export function SearchSourcesContextProvider(props: { children: ReactNode }) {
    const { children } = props;

    const sources = SearchService.pluggableSources;

    const initialSource = sources[0]!;

    const [currentSource, _setCurrentSource] = useState<ISearchSource>(initialSource);

    /**
     * This effect is responsible for halting all ongoing network request
     * except for the currently selected source
     */
    useEffect(() => {
        sources.forEach((source) => {
            if (source.key !== currentSource.key) {
                source.abort?.();
            }
        });
    }, [currentSource, sources]);

    const setCurrentSource = (sourceKey: ISearchSource["key"]) => {
        const matchingSource = SearchService.pluggableSources.find(({ key }) => key === sourceKey);
        if (matchingSource) {
            _setCurrentSource(matchingSource);
        }
    };

    return (
        <SearchSourcesContext.Provider value={{ sources, currentSource, setCurrentSource }}>
            {children}
        </SearchSourcesContext.Provider>
    );
}

export function useSearchSources() {
    return useContext(SearchSourcesContext);
}
