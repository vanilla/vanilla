/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import SearchBar from "@library/features/search/SearchBar";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { EmptySearchScopeProvider } from "@library/features/search/SearchScopeContext";
import { t } from "@vanilla/i18n";
import { css } from "@emotion/css";

export function AutomationRulesSearchbar(props: { onSearch: (query: string) => void; isLoading?: boolean }) {
    const [searchValue, setSearchValue] = useState<string>("");

    return (
        <EmptySearchScopeProvider>
            <SearchBar
                onChange={(newValue) => {
                    setSearchValue(newValue);
                }}
                value={searchValue}
                onSearch={() => {
                    props.onSearch(searchValue);
                }}
                triggerSearchOnClear={true}
                titleAsComponent={t("Search")}
                disableAutocomplete={true}
                overwriteSearchBar={{
                    preset: SearchBarPresets.BORDER,
                }}
                isLoading={props.isLoading}
                className={css({ maxWidth: 480 })}
            />
        </EmptySearchScopeProvider>
    );
}
