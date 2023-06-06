/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { StackableTableSortOption, StackableTableColumnsConfig } from "@dashboard/tables/StackableTable/StackableTable";

export type UserManagementTableColumnName = "username" | "roles" | "first visit" | "last visit" | "last ip";

export type UserSortParams =
    | "dateInserted"
    | "dateLastActive"
    | "name"
    | "userID"
    | "-dateInserted"
    | "-dateLastActive"
    | "-name"
    | "-userID";

export enum UserManagementColumnNames {
    USER_NAME = "username",
    ROLES = "roles",
    FIRST_VISIT = "first visit",
    LAST_VISIT = "last visit",
    LAST_IP = "last ip",
}

export const INITIAL_CONFIGURATION: StackableTableColumnsConfig = {
    username: {
        order: 1,
        hidden: false,
        wrapped: false,
        sortDirection: StackableTableSortOption.DESC,
    },
    roles: {
        order: 2,
        hidden: false,
        wrapped: false,
    },
    "first visit": {
        order: 3,
        hidden: false,
        sortDirection: StackableTableSortOption.NO_SORT,
        wrapped: false,
    },
    "last visit": {
        order: 4,
        hidden: false,
        sortDirection: StackableTableSortOption.NO_SORT,
        wrapped: false,
    },
    "last ip": {
        order: 5,
        hidden: false,
        wrapped: false,
    },
};

/**
 * This will generate the right sort param out of sort value and direction for our search query params.
 *
 * @param columnName - Column name.
 * @param sortoption - Wether is ascending or descending.
 */
export const mapSortOptionToQueryParam = (
    columnName: UserManagementTableColumnName,
    sortOption: StackableTableSortOption,
) => {
    if (columnName && sortOption) {
        const isDescending = sortOption === StackableTableSortOption.DESC;
        switch (columnName) {
            case UserManagementColumnNames.USER_NAME:
                return isDescending ? "name" : "-name";
            case UserManagementColumnNames.FIRST_VISIT:
                return isDescending ? "-dateInserted" : "dateInserted";
            case UserManagementColumnNames.LAST_VISIT:
                return isDescending ? "-dateLastActive" : "dateLastActive";
        }
    }
    return null;
};
