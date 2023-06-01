/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ComponentType, useMemo, useRef, useState } from "react";
import { useLocation } from "react-router";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { t } from "@vanilla/i18n";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import SmartLink from "@library/routing/links/SmartLink";
import QueryString from "@library/routing/QueryString";
import { searchBarClasses } from "@library/features/search/SearchBar.styles";
import SearchBar from "@library/features/search/SearchBar";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import qs from "qs";
import { UserManagementProvider, useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { cx } from "@emotion/css";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { IGetUsersParams, useGetUsers } from "@dashboard/users/userManagement/UserManagement.hooks";
import { EmptySearchScopeProvider } from "@library/features/search/SearchScopeContext";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import Translate from "@library/content/Translate";
import UserManagementTable from "./UserManagementTable";
import ErrorMessages from "@library/forms/ErrorMessages";

export function UserManagementImpl() {
    const { permissions, RanksWrapperComponent, ...rest } = useUserManagement();
    const location = useLocation();
    const { search: browserQuery } = location;
    const queryFromUrl: any = qs.parse(browserQuery, { ignoreQueryPrefix: true });
    const [searchValue, setSearchValue] = useState<string>(queryFromUrl.Keywords ?? "");

    const [query, setQuery] = useState<IGetUsersParams>({
        query: queryFromUrl.Keywords ?? "",
        page: queryFromUrl.page ?? 1,
    });
    const classes = userManagementClasses();
    const userTableRef = useRef<HTMLDivElement>(null);

    const { error, isFetching, data } = useGetUsers(query);

    const updateQuery = (newPage: number) => {
        setQuery({ ...query, page: newPage ?? 1 });

        if (newPage) {
            window.scrollTo({ top: userTableRef.current?.offsetTop ? userTableRef.current?.offsetTop - 10 : 0 });
        }
    };

    const countUsers = useMemo(() => {
        if (data) {
            return (
                <Translate
                    source={"<0/> <1/> found."}
                    c0={data.countUsers}
                    c1={parseInt(data.countUsers) > 1 ? "users" : "user"}
                />
            );
        }
        return <></>;
    }, [data]);

    return (
        <>
            <QueryString value={{ Keywords: query.query, page: query.page }} />
            {permissions.canAddUsers && (
                <ConditionalWrap
                    condition={Boolean(RanksWrapperComponent)}
                    component={RanksWrapperComponent as ComponentType<any>}
                >
                    <DashboardAddEditUser {...rest} newUserManagement />
                </ConditionalWrap>
            )}
            <div className={cx(dashboardClasses().extendRow)}>
                <div className={cx(searchBarClasses({}).standardContainer, classes.searchAndActionsContainer)}>
                    <div className={classes.searchAndCountContainer}>
                        <EmptySearchScopeProvider>
                            <SearchBar
                                onChange={(newValue) => {
                                    setSearchValue(newValue);
                                }}
                                value={searchValue}
                                onSearch={() => {
                                    setQuery({ ...query, query: searchValue });
                                }}
                                triggerSearchOnClear={true}
                                titleAsComponent={t("Search")}
                                handleOnKeyDown={(event) => {
                                    if (event.key === "Enter") {
                                        setQuery({ ...query, query: searchValue });
                                    }
                                }}
                                disableAutocomplete={true}
                                needsPageTitle={false}
                                overwriteSearchBar={{
                                    preset: SearchBarPresets.BORDER,
                                }}
                            />
                        </EmptySearchScopeProvider>
                        <div className={classes.countUsers}>{countUsers}</div>
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
                                onChange={(page: number) => updateQuery(page)}
                            />
                        )}
                    </div>
                </div>
            </div>
            <div className={cx(dashboardClasses().extendRow, classes.tableContainer)}>
                {error && <ErrorMessages errors={[error]} />}
                {data && <UserManagementTable data={data} />}
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
