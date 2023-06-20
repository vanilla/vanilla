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
    UserManagementTableColumnName,
    mapSortOptionToQueryParam,
} from "@dashboard/users/userManagement/UserManagementUtils";
import UserManagementTableCell, {
    WrappedCell,
    ActionsCell,
} from "@dashboard/users/userManagement/UserManagementTableCell";
import UserManagementColumnsConfig from "@dashboard/users/userManagement/UserManagementColumnsConfig";
import { spaceshipCompare } from "@vanilla/utils";
import { useSessionStorage } from "@vanilla/react-utils";

export function UserManagementImpl() {
    const { permissions, RanksWrapperComponent, currentUserID, profileFields, ...rest } = useUserManagement();
    const location = useLocation();
    const { search: browserQuery } = location;
    const queryFromUrl: any = qs.parse(browserQuery, { ignoreQueryPrefix: true });
    const [query, setQuery] = useState<IGetUsersQueryParams>({
        query: queryFromUrl.Keywords ?? "",
        page: queryFromUrl.page ?? 1,
        roleID: queryFromUrl.roleID,
        sort: queryFromUrl.sort ?? "name",
    });
    const [configuration, setConfiguration] = useSessionStorage<StackableTableColumnsConfig>(
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

    const { error, isFetching, data } = useGetUsers(query);

    const updateQuery = (newParams: IGetUsersQueryParams) => {
        //if this is a new search term lets go to first page
        if (newParams.query && newParams.query !== query.query) {
            newParams.page = 1;
        }
        setQuery({ ...query, ...newParams });

        if (newParams.page) {
            window.scrollTo({ top: userTableRef.current?.offsetTop ? userTableRef.current?.offsetTop - 10 : 0 });
        }
    };

    const handleSort = (columnName: UserManagementTableColumnName, sortOption: StackableTableSortOption) => {
        if (columnName && sortOption) {
            const sortValue = mapSortOptionToQueryParam(columnName, sortOption);
            if (sortValue) {
                updateQuery({ sort: sortValue });
            }
        }
    };

    return (
        <>
            <QueryString value={{ Keywords: query.query, page: query.page, roleID: query.roleID, sort: query.sort }} />
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
                        <UserManagementSearchbar initialValue={query.query ?? ""} updateQuery={updateQuery} />
                        <div className={classes.countUsers}>
                            {data && (
                                <Translate
                                    source={"<0/> <1/> found."}
                                    c0={data.countUsers}
                                    c1={parseInt(data.countUsers) > 1 ? "users" : "user"}
                                />
                            )}
                        </div>
                    </div>
                    <div className={classes.actionsContainer}>
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
                        {data && (
                            <NumberedPager
                                {...{
                                    totalResults: parseInt(data?.countUsers),
                                    currentPage: parseInt(data?.currentPage),
                                    pageLimit: 30,
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
                {error && <ErrorMessages errors={[error]} />}
                {data && data.users && (
                    <StackableTable
                        data={data.users}
                        updateQuery={updateQuery}
                        onHeaderClick={handleSort}
                        hiddenHeaders={["actions"]}
                        isLoading={isFetching}
                        columnsConfiguration={configuration}
                        CellRenderer={UserManagementTableCell}
                        WrappedCellRenderer={WrappedCell}
                        ActionsCellRenderer={ActionsCell}
                        actionsColumnWidth={80}
                        className={classes.table}
                    />
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
