/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";

import SearchBar from "@library/features/search/SearchBar";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { EmptySearchScopeProvider } from "@library/features/search/SearchScopeContext";
import { t } from "@vanilla/i18n";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";

interface IProps {
    initialValue: string;
    updateQuery: (newQueryParams: IGetUsersQueryParams) => void;
    isLoading?: boolean;
    currentQuery: IGetUsersQueryParams;
}
export default function UserManagementSearchbar(props: IProps) {
    const { initialValue, updateQuery, isLoading, currentQuery } = props;
    const [searchValue, setSearchValue] = useState<string>(initialValue ?? "");

    return (
        <EmptySearchScopeProvider>
            <SearchBar
                onChange={(newValue) => {
                    setSearchValue(newValue);
                }}
                value={searchValue}
                onSearch={() => {
                    updateQuery({ ...currentQuery, query: searchValue });
                }}
                triggerSearchOnClear={true}
                titleAsComponent={t("Search")}
                handleOnKeyDown={(event) => {
                    if (event.key === "Enter") {
                        updateQuery({ ...currentQuery, query: searchValue });
                    }
                }}
                disableAutocomplete={true}
                needsPageTitle={false}
                overwriteSearchBar={{
                    preset: SearchBarPresets.BORDER,
                }}
                isLoading={isLoading}
            />
        </EmptySearchScopeProvider>
    );
}
