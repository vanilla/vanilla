/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { StackableTableSortOption, StackableTableColumnsConfig } from "@dashboard/tables/StackableTable/StackableTable";
import { t } from "@vanilla/i18n";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { IRole } from "@dashboard/roles/roleTypes";
import { dateRangeToString, dateStringInUrlToDateRange } from "@library/search/SearchUtils";
import { IGetUsersQueryParams } from "./UserManagement.hooks";
import { isDateRange } from "@dashboard/components/panels/FilteredProfileFields";
import { ProfileField, ProfileFieldDataType } from "@dashboard/userProfiles/types/UserProfiles.types";

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
    | "-userID"
    | "countPosts"
    | "-countPosts"
    | "points"
    | "-points";

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

/**
 * Map a column name to a entry the API select middleware.
 *
 * @param columnName The column name.
 * @param profileFields Profile field data.
 * @returns An array of tuples to be used in Object.fromEntries().
 */
export function mapColumnNameToSelectEntry(
    columnName: string,
    profileFields: ProfileField[],
): Array<[fieldName: string, label: string]> {
    switch (columnName) {
        case UserManagementColumnNames.FIRST_VISIT:
            return [["dateInserted", t("First Visit")]];
        case UserManagementColumnNames.ROLES:
            return [["roles", t("Roles")]];
        case UserManagementColumnNames.LAST_VISIT:
            return [["dateLastActive", t("Last Visit")]];
        case UserManagementColumnNames.LAST_IP:
            return [["lastIPAddress", t("Last IP")]];
        case UserManagementColumnNames.REGISTER_IP:
            return [["insertIPAddress", t("Register IP")]];
        case UserManagementColumnNames.USER_NAME:
            return [
                ["name", t("Username")],
                ["email", t("Email")],
                ["url", t("URL")],
            ];
        case UserManagementColumnNames.USER_ID:
            return [["userID", t("User ID")]];
        case UserManagementColumnNames.RANK:
            return [
                ["rank.name", t("Rank Name")],
                ["rank.rankID", t("Rank ID")],
            ];
        case UserManagementColumnNames.POSTS:
            return [["countPosts", t("Posts")]];
        case UserManagementColumnNames.POINTS:
            return [["points", t("Points")]];
        default: {
            const profileField = profileFields.find((field) => field.apiName === columnName);
            if (!profileField) {
                return [];
            }

            return [[`profileFields.${columnName}`, profileField.label]];
        }
    }
}

export const USERS_LIMIT_PER_PAGE = 30;
export const USERS_MAX_PAGE_COUNT = 10000;

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
    UserManagementColumnNames.POSTS,
    UserManagementColumnNames.POINTS,
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
            case UserManagementColumnNames.POSTS:
                return isDescending ? "-countPosts" : "countPosts";
            case UserManagementColumnNames.POINTS:
                return isDescending ? "-points" : "points";
        }
    }
    return null;
};

interface IBaseSchema {
    ipAddresses?: string;
    dateInserted?: { start?: string; end?: string };
    dateLastActive?: { start?: string; end?: string };
    roleIDs?: number[];
}

/**
 * This generates filter schema for user management.
 *
 */
export const getBaseFilterSchema = (): JsonSchema<IBaseSchema> => {
    return {
        type: "object",
        properties: {
            roleIDs: {
                type: "array",
                items: { type: "integer" },
                default: [],
                nullable: true,
                "x-control": {
                    legend: t("Roles"),
                    inputType: "custom",
                    component: MultiRoleInput,
                },
            },
            dateInserted: {
                type: "object",
                nullable: true,
                "x-control": {
                    label: t("First Visit"),
                    inputType: "dateRange",
                },
                properties: {
                    start: {
                        nullable: true,
                        type: "string",
                    },
                    end: {
                        nullable: true,
                        type: "string",
                    },
                },
            },
            dateLastActive: {
                type: "object",
                nullable: true,
                "x-control": {
                    label: t("Last Visit"),
                    inputType: "dateRange",
                },
                properties: {
                    start: {
                        nullable: true,
                        type: "string",
                    },
                    end: {
                        nullable: true,
                        type: "string",
                    },
                },
            },
            ipAddresses: {
                type: "string",
                nullable: true,
                "x-control": {
                    label: t("IP Address"),
                    inputType: "textBox",
                },
            },
        },
        required: [],
    };
};

export interface IUserManagementFilterValues {
    roleIDs?: Array<IRole["roleID"]>;
    rankIDs?: number[];
    dateInserted?: { start?: string; end?: string };
    dateLastActive?: { start?: string; end?: string };
    ipAddresses?: string;
    profileFields?: {
        [key: string]: any;
    };
}

/**
 * This will generate the right search query params out of filter values.
 *
 * @param values - Current filter values.
 */
export const mapFilterValuesToQueryParams = (values: IUserManagementFilterValues) => {
    // format date ranges nested in profile fields
    const profileFieldFilters = { ...values.profileFields };
    for (let key in values.profileFields) {
        const profileFieldFilterValue = values.profileFields[key];
        if (isDateRange(profileFieldFilterValue)) {
            profileFieldFilters[key] = dateRangeToString(profileFieldFilterValue);
        }
    }

    const dateInsertedValue =
        values.dateInserted &&
        dateRangeToString({
            start: values.dateInserted.start,
            end: values.dateInserted.end,
        });

    const dateLastActiveValue =
        values.dateLastActive &&
        dateRangeToString({
            start: values.dateLastActive.start,
            end: values.dateLastActive.end,
        });

    return {
        ...values,
        ...(dateInsertedValue && {
            dateInserted: dateInsertedValue,
        }),
        ...(dateLastActiveValue && {
            dateLastActive: dateLastActiveValue,
        }),
        ...(values.ipAddresses && {
            ipAddresses: [values.ipAddresses],
        }),
        ...(profileFieldFilters &&
            Object.keys(profileFieldFilters).length && {
                profileFields: profileFieldFilters,
            }),
    } as IGetUsersQueryParams;
};

/**
 * Returns query params from query in url.
 *
 * @param queryFromUrl - Object created from query string in url.
 */
export const mapUrlQueryToQueryParams = (queryFromUrl: any): IGetUsersQueryParams => {
    return {
        query: queryFromUrl.Keywords ?? "",
        page: queryFromUrl.page ?? 1,
        sort: queryFromUrl.sort ?? "name",
        ...(queryFromUrl.roleIDs &&
            queryFromUrl.roleIDs.length && { roleIDs: queryFromUrl.roleIDs.map((roleID) => parseInt(roleID)) }),
        ...(queryFromUrl.rankIDs &&
            queryFromUrl.rankIDs.length && { rankIDs: queryFromUrl.rankIDs.map((rankID) => parseInt(rankID)) }),
        ...(queryFromUrl.dateInserted && { dateInserted: queryFromUrl.dateInserted }),
        ...(queryFromUrl.dateLastActive && { dateLastActive: queryFromUrl.dateLastActive }),
        ...(queryFromUrl.ipAddresses && { ipAddresses: queryFromUrl.ipAddresses }),
        ...(queryFromUrl.profileFields && { profileFields: queryFromUrl.profileFields }),
    };
};

/**
 * Translates filter values for our form from current query.
 *
 * @param currentQuery - Current query.
 * @param profileFields - All profile fields.
 */
export const mapQueryParamsToFilterValues = (currentQuery: IGetUsersQueryParams, profileFields?: ProfileField[]) => {
    const { query, page, sort, ...filterValues } = currentQuery;

    // format date ranges nested in profile fields
    const profileFieldFilters = { ...filterValues.profileFields };
    for (let key in filterValues.profileFields) {
        const profileFieldFilterValue = filterValues.profileFields[key];
        const isDateType = profileFields?.find((field) => {
            return field.apiName === key && field.dataType === ProfileFieldDataType.DATE;
        });
        if (isDateType) {
            profileFieldFilters[key] = dateStringInUrlToDateRange(profileFieldFilterValue);
        }
    }

    return {
        ...filterValues,
        ...(!!filterValues.dateInserted && { dateInserted: dateStringInUrlToDateRange(filterValues.dateInserted) }),
        ...(!!filterValues.dateLastActive && {
            dateLastActive: dateStringInUrlToDateRange(filterValues.dateLastActive),
        }),
        ...(!!filterValues.ipAddresses && { lastIPAddress: filterValues.ipAddresses }),
        ...(profileFieldFilters && Object.keys(profileFieldFilters).length && { profileFields: profileFieldFilters }),
    };
};
