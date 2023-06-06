/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ComponentType, useRef, useState } from "react";
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
import StackableTable, { StackableTableSortOption } from "@dashboard/tables/StackableTable/StackableTable";
import ErrorMessages from "@library/forms/ErrorMessages";
import UserManagementSearchbar from "@dashboard/users/userManagement/UserManagementSearchBar";
import {
    INITIAL_CONFIGURATION,
    UserManagementTableColumnName,
    mapSortOptionToQueryParam,
} from "@dashboard/users/userManagement/UserManagementUtils";
import UserManagementTableCell from "@dashboard/users/userManagement/UserManagementCell";
import UserManagementTableWrappedCell from "@dashboard/users/userManagement/UserManagementWrappedCell";
import UserManagementTableActionsCell from "@dashboard/users/userManagement/UserManagementActionsCell";

export function UserManagementImpl() {
    const { permissions, RanksWrapperComponent, ...rest } = useUserManagement();
    const location = useLocation();
    const { search: browserQuery } = location;
    const queryFromUrl: any = qs.parse(browserQuery, { ignoreQueryPrefix: true });
    const [query, setQuery] = useState<IGetUsersQueryParams>({
        query: queryFromUrl.Keywords ?? "",
        page: queryFromUrl.page ?? 1,
        roleID: queryFromUrl.roleID,
        sort: queryFromUrl.sort ?? "name",
    });

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
                    <DashboardAddEditUser {...rest} newUserManagement />
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
                        columnsConfiguration={INITIAL_CONFIGURATION}
                        CellRenderer={UserManagementTableCell}
                        WrappedCellRenderer={UserManagementTableWrappedCell}
                        ActionsCellRenderer={UserManagementTableActionsCell}
                    />
                )}
            </div>
            <DashboardHelpAsset>
                <h3>{t("HEADS UP!")}</h3>
                <p>{t("Some text here, to come ...")}</p>
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
