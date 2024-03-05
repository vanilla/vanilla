/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ISearchSource } from "@library/search/searchTypes";
import React, { useContext } from "react";
import { SearchService } from "@library/search/SearchService";

interface ISearchSourcesContext {
    sources: ISearchSource[];
}

export function useSearchSources() {
    return useContext(
        React.createContext<ISearchSourcesContext>({
            sources: SearchService.sources,
        }),
    );
}
