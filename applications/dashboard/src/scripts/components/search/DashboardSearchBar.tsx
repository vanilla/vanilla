/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import SearchBar from "@library/features/search/SearchBar";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { EmptySearchScopeProvider } from "@library/features/search/SearchScopeContext";
import { t } from "@vanilla/i18n";

interface IProps extends Pick<React.ComponentProps<typeof SearchBar>, "placeholder"> {
    initialValue: string;
    updateQuery: (newQuery: string) => void;
    isLoading?: boolean;
}

export default function DashboardSearchBar(props: IProps) {
    const { initialValue, updateQuery, isLoading, placeholder } = props;
    const [searchValue, setSearchValue] = useState<string>(initialValue ?? "");

    return (
        <EmptySearchScopeProvider>
            <SearchBar
                onChange={(newValue) => {
                    setSearchValue(newValue);
                }}
                value={searchValue}
                onSearch={() => {
                    updateQuery(searchValue);
                }}
                triggerSearchOnClear={true}
                titleAsComponent={t("Search")}
                placeholder={placeholder}
                disableAutocomplete={true}
                overwriteSearchBar={{
                    preset: SearchBarPresets.BORDER,
                }}
                isLoading={isLoading}
            />
        </EmptySearchScopeProvider>
    );
}
