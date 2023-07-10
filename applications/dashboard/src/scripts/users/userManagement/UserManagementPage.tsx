/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ComponentType, useEffect, useMemo, useRef, useState } from "react";
import { useLocation } from "react-router";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { t } from "@vanilla/i18n";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import SmartLink from "@library/routing/links/SmartLink";
import QueryString from "@library/routing/QueryString";
import qs from "qs";
import { UserManagementProvider, useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { cx } from "@emotion/css";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { IGetUsersQueryParams, useGetUsers } from "@dashboard/users/userManagement/UserManagement.hooks";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import Translate from "@library/content/Translate";
import StackableTable, {
    StackableTableColumnsConfig,
    StackableTableSortOption,
} from "@dashboard/tables/StackableTable/StackableTable";
import ErrorMessages from "@library/forms/ErrorMessages";
import UserManagementSearchbar from "@dashboard/users/userManagement/UserManagementSearchBar";
import {
    DEFAULT_ADDITIONAL_STATIC_COLUMNS,
    DEFAULT_CONFIGURATION,
    USERS_MAX_PAGE_COUNT,
    USERS_LIMIT_PER_PAGE,
    UserManagementTableColumnName,
    mapSortOptionToQueryParam,
    IUserManagementFilterValues,
    mapUrlQueryToQueryParams,
    mapQueryParamsToFilterValues,
} from "@dashboard/users/userManagement/UserManagementUtils";
import UserManagementTableCell, {
    WrappedCell,
    ActionsCell,
} from "@dashboard/users/userManagement/UserManagementTableCell";
import UserManagementColumnsConfig from "@dashboard/users/userManagement/UserManagementColumnsConfig";
import { spaceshipCompare } from "@vanilla/utils";
import { useLocalStorage } from "@vanilla/react-utils";
import { ToolTip } from "@library/toolTip/ToolTip";
import UserManagementFilter from "@dashboard/users/userManagement/UserManagementFilter";
import { SiteTotalsCountOption, useGetSiteTotalsCount } from "@library/siteTotals/SiteTotals.hooks";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import { dateStringInUrlToDateRange } from "@library/search/SearchUtils";

export function UserManagementImpl() {
    const { permissions, RanksWrapperComponent, currentUserID, profileFields, ...rest } = useUserManagement();
    const location = useLocation();
    const { search: browserQuery } = location;
    const queryFromUrl: any = qs.parse(browserQuery, { ignoreQueryPrefix: true });

    const [query, setQuery] = useState<IGetUsersQueryParams>(mapUrlQueryToQueryParams(queryFromUrl));

    const [configuration, setConfiguration] = useLocalStorage<StackableTableColumnsConfig>(
        `${currentUserID}_userManagement_columns_config`,
        DEFAULT_CONFIGURATION,
    );

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
                updateQuery({ ...query, sort: sortValue });
            }
        }
    };
    const initialFilterValues = mapQueryParamsToFilterValues(query, profileFields) as IUserManagementFilterValues;
    const countUsersResults = data?.countUsers && parseInt(data?.countUsers);

    return (
        <>
            <QueryString
                value={{
                    Keywords: query.query,
                    page: query.page,
                    roleIDs: query.roleIDs,
                    sort: query.sort,
                    rankIDs: query.rankIDs,
                    dateInserted: query.dateInserted,
                    dateLastActive: query.dateLastActive,
                    ipAddresses: query.ipAddresses,
                    profileFields: query.profileFields,
                }}
            />
            {permissions.canAddUsers && (
                <ConditionalWrap
                    condition={Boolean(RanksWrapperComponent)}
                    component={RanksWrapperComponent as ComponentType<any>}
                >
                    <DashboardAddEditUser {...rest} profileFields={profileFields} newUserManagement />
                </ConditionalWrap>
            )}
            <div className={cx(dashboardClasses().extendRow)}>
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
                                onChange={(page: number) => updateQuery({ page: page })}
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
                <SmartLink to={"https://success.vanillaforums.com/kb/articles/27-moderation-overview#managing-users"}>
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
