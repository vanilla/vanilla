/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { StackableTableSortOption, StackableTableColumnsConfig } from "@dashboard/tables/StackableTable/StackableTable";

export type UserManagementTableColumnName =
    | "username"
    | "roles"
    | "first visit"
    | "last visit"
    | "last ip"
    | "register ip"
    | "user id"
    | "rank"
    | "posts"
    | "points"
    | string; // we can't control profile field name

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
    REGISTER_IP = "register ip",
    USER_ID = "user id",
    RANK = "rank",
    POSTS = "posts",
    POINTS = "points",
}

export const DEFAULT_CONFIGURATION: StackableTableColumnsConfig = {
    username: {
        order: 1,
        isHidden: false,
        wrapped: false,
        sortDirection: StackableTableSortOption.DESC,
    },
    roles: {
        order: 2,
        isHidden: false,
        wrapped: false,
    },
    "first visit": {
        order: 3,
        isHidden: false,
        sortDirection: StackableTableSortOption.NO_SORT,
        wrapped: false,
    },
    "last visit": {
        order: 4,
        isHidden: false,
        sortDirection: StackableTableSortOption.NO_SORT,
        wrapped: false,
    },
    "last ip": {
        order: 5,
        isHidden: false,
        wrapped: false,
    },
};

export const DEFAULT_ADDITIONAL_STATIC_COLUMNS = [
    UserManagementColumnNames.REGISTER_IP,
    UserManagementColumnNames.USER_ID,
    UserManagementColumnNames.RANK,
    UserManagementColumnNames.POSTS,
    UserManagementColumnNames.POINTS,
];
export const SORTABLE_COLUMNS = [
    UserManagementColumnNames.USER_NAME,
    UserManagementColumnNames.FIRST_VISIT,
    UserManagementColumnNames.LAST_VISIT,
    UserManagementColumnNames.USER_ID,
];

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
            case UserManagementColumnNames.USER_ID:
                return isDescending ? "userID" : "-userID";
        }
    }
    return null;
};
