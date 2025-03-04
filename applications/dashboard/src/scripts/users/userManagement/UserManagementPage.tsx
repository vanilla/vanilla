/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import StackableTable, {
    StackableTableColumnsConfig,
    StackableTableSortOption,
} from "@dashboard/tables/StackableTable/StackableTable";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { IGetUsersQueryParams, useGetUsers } from "@dashboard/users/userManagement/UserManagement.hooks";
import UserManagementColumnsConfig from "@dashboard/users/userManagement/UserManagementColumnsConfig";
import { UserManagementProvider, useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import UserManagementFilter from "@dashboard/users/userManagement/UserManagementFilter";
import UserManagementSearchbar from "@dashboard/users/userManagement/UserManagementSearchBar";
import UserManagementTableCell, {
    ActionsCell,
    WrappedCell,
} from "@dashboard/users/userManagement/UserManagementTableCell";
import {
    DEFAULT_ADDITIONAL_STATIC_COLUMNS,
    DEFAULT_CONFIGURATION,
    IUserManagementFilterValues,
    mapQueryParamsToFilterValues,
    mapSortOptionToQueryParam,
    mapUrlQueryToQueryParams,
    UserManagementTableColumnName,
    USERS_LIMIT_PER_PAGE,
    USERS_MAX_PAGE_COUNT,
} from "@dashboard/users/userManagement/UserManagementUtils";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import Translate from "@library/content/Translate";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { dropdownSwitchButtonClasses } from "@library/flyouts/dropDownSwitchButtonStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import ErrorMessages from "@library/forms/ErrorMessages";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import ButtonLoader from "@library/loaders/ButtonLoader";
import SmartLink from "@library/routing/links/SmartLink";
import QueryString from "@library/routing/QueryString";
import { SiteTotalsCountOption, useGetSiteTotalsCount } from "@library/siteTotals/SiteTotals.hooks";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { useLocalStorage } from "@vanilla/react-utils";
import { spaceshipCompare } from "@vanilla/utils";
import qs from "qs";
import React, { ComponentType, useEffect, useMemo, useRef, useState } from "react";
import { useLocation } from "react-router";
import { useUsersExport } from "./UserManagementExport";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

export function UserManagementImpl() {
    const { permissions, RanksWrapperComponent, currentUserID, profileFields, ...rest } = useUserManagement();
    const location = useLocation();
    const { search: browserQuery } = location;
    const queryFromUrl: any = qs.parse(browserQuery, { ignoreQueryPrefix: true });
    const { hasPermission } = usePermissionsContext();

    const canExportData = hasPermission("exports.manage");

    const [query, setQuery] = useState<IGetUsersQueryParams>(mapUrlQueryToQueryParams(queryFromUrl));

    const [configuration, setConfiguration] = useLocalStorage<StackableTableColumnsConfig>(
        `${currentUserID}_userManagement_columns_config`,
        DEFAULT_CONFIGURATION,
    );

    // this bit is to handle sticky header, it won't work if one of the parents has overflow other than visible
    // this will probably go away when we fully convert user management page into react (with left side panel etc)
    const stickyHeaderRef = useRef<HTMLDivElement | null>(null);
    useEffect(() => {
        if (stickyHeaderRef.current) {
            let parent = stickyHeaderRef.current.parentElement;
            while (parent) {
                const hasOverflow = getComputedStyle(parent).overflow;
                if (hasOverflow !== "visible") {
                    parent.style.overflow = "visible";
                }
                parent = parent.className !== "dashboard-main" ? parent.parentElement : null;
            }
        }
    }, []);

    // we need to check profile field columns with actual profile fields,
    // as its enabled status might already be changed, we should update current configuration
    useEffect(() => {
        if (profileFields) {
            const profileFieldsInConfig = Object.keys(configuration).filter((column) => configuration[column].columnID);
            const currentProfileFieldsIds = profileFields.map((profileField) => profileField.apiName);
            const invalidProfileFieldColumns = profileFieldsInConfig.filter(
                (profileField) =>
                    configuration[profileField].columnID &&
                    !currentProfileFieldsIds.includes(configuration[profileField].columnID as string),
            );

            if (invalidProfileFieldColumns && invalidProfileFieldColumns.length) {
                const newConfiguration = { ...configuration };
                invalidProfileFieldColumns.forEach((profileFieldColumn) => delete newConfiguration[profileFieldColumn]);
                setConfiguration(newConfiguration);
            }
        }
    }, [profileFields]);

    const orderedColumnsFromConfig = useMemo(() => {
        return Object.keys(configuration).sort((columnA, columnB) => {
            const columnAOrder = configuration[columnA].order;
            const columnBOrder = configuration[columnB].order;
            return spaceshipCompare(columnAOrder, columnBOrder);
        });
    }, [configuration]);

    const additionalColumns = useMemo(() => {
        const excludedMainColumns = Object.keys(DEFAULT_CONFIGURATION).filter(
            (mainColumn) => !orderedColumnsFromConfig.includes(mainColumn),
        );
        const columns = [...excludedMainColumns, ...DEFAULT_ADDITIONAL_STATIC_COLUMNS].filter(
            (column) => !orderedColumnsFromConfig.includes(column),
        );
        if (profileFields) {
            const profileFieldColumns = profileFields.map((field) => field.label);
            return [
                ...columns,
                ...profileFieldColumns.filter(
                    (profileFieldColumn) => !orderedColumnsFromConfig.includes(profileFieldColumn),
                ),
            ];
        }
        return columns;
    }, [profileFields, orderedColumnsFromConfig]);

    const classes = userManagementClasses();
    const userTableRef = useRef<HTMLDivElement>(null);

    const { error: countError, data: countData } = useGetSiteTotalsCount([SiteTotalsCountOption.USER]);

    const { error, isFetching, data } = useGetUsers(query);

    const updateQuery = (newParams: IGetUsersQueryParams) => {
        //if this is a new search term lets go to first page
        if (newParams.query && newParams.query !== query.query) {
            newParams.page = 1;
        }

        setQuery({
            ...query,
            ...newParams,
            ...{
                roleIDs: newParams.roleIDs,
                isBanned: newParams.isBanned,
                rankIDs: newParams.rankIDs,
                dateInserted: newParams.dateInserted,
                dateLastActive: newParams.dateLastActive,
                ipAddresses: newParams.ipAddresses,
            },
            ...{ profileFields: newParams.profileFields },
        });

        if (newParams.page) {
            window.scrollTo({ top: userTableRef.current?.offsetTop ? userTableRef.current?.offsetTop - 10 : 0 });
        }
    };

    const handleSort = (columnName: UserManagementTableColumnName, sortOption: StackableTableSortOption) => {
        if (columnName && sortOption) {
            const sortValue = mapSortOptionToQueryParam(columnName, sortOption);
            if (sortValue) {
                updateQuery({ ...query, sort: sortValue, page: 1 });
            }
        }
    };
    const initialFilterValues = mapQueryParamsToFilterValues(query, profileFields) as IUserManagementFilterValues;
    const countUsersResults = data?.countUsers && parseInt(data?.countUsers);

    // These are just the visible columns.
    const fieldsToExport: string[] = [];
    for (const [fieldName, fieldOptions] of Object.entries(configuration)) {
        if (!fieldOptions.isHidden) {
            fieldsToExport.push(fieldOptions.columnID ?? fieldName);
        }
    }
    const userExport = useUsersExport(fieldsToExport);

    return (
        <>
            <QueryString
                value={{
                    Keywords: query.query,
                    page: query.page,
                    roleIDs: query.roleIDs,
                    sort: query.sort,
                    banned: query.isBanned != undefined ? (query.isBanned ? "true" : "false") : undefined,
                    rankIDs: query.rankIDs,
                    dateInserted: query.dateInserted,
                    dateLastActive: query.dateLastActive,
                    ipAddresses: query.ipAddresses,
                    profileFields: query.profileFields,
                }}
            />

            <div className={classes.headerContainer} ref={stickyHeaderRef}>
                {permissions.canAddUsers && (
                    <ConditionalWrap
                        condition={Boolean(RanksWrapperComponent)}
                        component={RanksWrapperComponent as ComponentType<any>}
                    >
                        <DashboardAddEditUser
                            {...rest}
                            extraActions={
                                canExportData && (
                                    <DropDown flyoutType={FlyoutType.LIST}>
                                        <DropDownItemButton
                                            disabled={userExport.isFetching}
                                            onClick={() => {
                                                void userExport.exportUsers(query);
                                            }}
                                        >
                                            <span className={dropdownSwitchButtonClasses().itemLabel}>
                                                {t("Export User Data")}
                                            </span>
                                            <span className={dropdownSwitchButtonClasses().checkContainer}>
                                                {userExport.isFetching && <ButtonLoader />}
                                            </span>
                                        </DropDownItemButton>
                                    </DropDown>
                                )
                            }
                            profileFields={profileFields}
                            newUserManagement
                        />
                    </ConditionalWrap>
                )}
                {userExport.cancelDialogue}

                <div className={classes.searchAndActionsContainer}>
                    <div className={classes.searchAndCountContainer}>
                        <UserManagementSearchbar
                            initialValue={query.query ?? ""}
                            updateQuery={updateQuery}
                            currentQuery={query}
                        />
                        <div className={classes.countUsers}>
                            {countError && <ErrorMessages errors={[countError]} />}
                            {countData?.userCount && data?.countUsers && (
                                <Translate
                                    source={"<0/> out of <1/> users found."}
                                    c0={
                                        countUsersResults && countUsersResults >= USERS_MAX_PAGE_COUNT
                                            ? `${humanReadableNumber(countUsersResults)}+`
                                            : countUsersResults
                                    }
                                    c1={countData.userCount}
                                />
                            )}
                        </div>
                    </div>
                    <div className={classes.headerActionsContainer} data-testid="filter-columnsConfig-container">
                        <UserManagementFilter
                            updateQuery={updateQuery}
                            profileFields={profileFields}
                            initialFilters={initialFilterValues}
                        />

                        <UserManagementColumnsConfig
                            configuration={configuration}
                            onConfigurationChange={(newConfig: StackableTableColumnsConfig) => {
                                setConfiguration(newConfig);
                            }}
                            treeColumns={orderedColumnsFromConfig}
                            additionalColumns={additionalColumns}
                            profileFields={profileFields}
                        />
                    </div>
                    <div className={classes.pagerContainer}>
                        {data?.users && countData && (
                            <NumberedPager
                                {...{
                                    totalResults: parseInt(data?.countUsers),
                                    currentPage: parseInt(data?.currentPage),
                                    pageLimit: USERS_LIMIT_PER_PAGE,
                                    hasMorePages: countUsersResults ? countUsersResults >= USERS_MAX_PAGE_COUNT : false,
                                    className: classes.pager,
                                    showNextButton: false,
                                }}
                                onChange={(page: number) => updateQuery({ ...query, page: page })}
                                isMobile={false}
                            />
                        )}
                    </div>
                </div>
            </div>
            <div className={dashboardClasses().extendRow}>
                {error && (
                    <div style={{ padding: 18 }}>
                        <ErrorMessages errors={[error]} />
                    </div>
                )}
                {data?.users ? (
                    <StackableTable
                        data={data?.users}
                        updateQuery={updateQuery}
                        onHeaderClick={handleSort}
                        hiddenHeaders={["actions"]}
                        isLoading={isFetching}
                        columnsConfiguration={configuration}
                        CellRenderer={UserManagementTableCell}
                        WrappedCellRenderer={WrappedCell}
                        ActionsCellRenderer={ActionsCell}
                        headerWrappers={{
                            posts: function TablePostsHeaderWrapper({ children }) {
                                return (
                                    <ToolTip
                                        label={t("Post Count includes the total number of discussions and comments")}
                                    >
                                        <span>{children}</span>
                                    </ToolTip>
                                );
                            },
                        }}
                        actionsColumnWidth={80}
                        className={classes.table}
                    />
                ) : (
                    !isFetching && <div style={{ padding: 18 }}>{t("No results.")}</div>
                )}
            </div>
            <DashboardHelpAsset>
                <h3>{t("HEADS UP!")}</h3>
                <p>{t("You can search for users by username or email, wildcards are implied.")}</p>
                <h3>{t("NEED MORE HELP?")}</h3>
                <SmartLink to={"https://success.vanillaforums.com/kb/articles/1474-manage-users"}>
                    {t("Managing Users")}
                </SmartLink>
            </DashboardHelpAsset>
        </>
    );
}

export function UserManagementPage() {
    return (
        <UserManagementProvider>
            <ErrorPageBoundary>
                <UserManagementImpl />
            </ErrorPageBoundary>
        </UserManagementProvider>
    );
}

export default UserManagementPage;
