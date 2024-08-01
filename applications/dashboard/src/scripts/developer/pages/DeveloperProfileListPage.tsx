/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { DeveloperNav } from "@dashboard/developer/DeveloperNav";
import { DeveloperProfileDetailRoute } from "@dashboard/developer/getDeveloperRoutes";
import { developerProfileClasses } from "@dashboard/developer/pages/DeveloperProfilePage.classes";
import { DeveloperProfileOptionsMenu } from "@dashboard/developer/profileViewer/DeveloperProfile.OptionsMenu";
import {
    DeveloperProfileSort,
    DeveloperProfileSortOptions,
    useDeveloperProfilesQuery,
} from "@dashboard/developer/profileViewer/DeveloperProfiles.hooks";
import { DeveloperProfileMetas } from "@dashboard/developer/profileViewer/DeveloperProfiles.metas";
import { IApiError } from "@library/@types/api/core";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { PageBox } from "@library/layout/PageBox";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import Loader from "@library/loaders/Loader";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam, useQueryParamPage } from "@library/routing/routingUtils";
import { useState } from "react";

export function DeveloperProfilesListPage() {
    const [page, setPage] = useState(useQueryParamPage());
    const [sort, setSort] = useState<DeveloperProfileSort>("-dateRecorded");
    const [trackedValue, setTrackedValue] = useState<"true" | "false">(useQueryParam("tracked", "false"));
    const trackedOptions: Record<string, ISelectBoxItem> = {
        true: {
            name: "Only Tracked",
            value: "true",
        },
        false: {
            name: "All Profiles",
            value: "false",
        },
    };

    const query = useDeveloperProfilesQuery({ sort, page, isTracked: trackedValue === "true" ? true : undefined });
    useQueryStringSync(
        {
            page,
            sort,
            tracked: trackedValue,
        },
        {
            page: 1,
            tracked: "false",
        },
    );

    const classes = developerProfileClasses();
    const paginationProps: INumberedPagerProps = {
        totalResults: query.data?.pagination?.total,
        currentPage: query.data?.pagination?.currentPage,
        pageLimit: query.data?.pagination?.limit,
        hasMorePages: query.data?.pagination?.total ? query.data?.pagination?.total >= 10000 : false,
    };

    return (
        <AdminLayout
            title="Developer Profiles"
            leftPanel={<DeveloperNav />}
            rightPanel={
                <>
                    <div>
                        <h2>What is this?</h2>
                        <p>Developer Profiles are used to debug performance issues.</p>
                        <p>
                            Profiles are sampled at random on normal requests and always record for SysAdmin requests.
                            They are pruned frequently unless you mark a profile as <strong>tracked</strong>.
                        </p>
                    </div>
                </>
            }
            content={
                <div className={classes.listContent}>
                    <ProfileList
                        query={query}
                        filters={
                            <div className={classes.listHeader}>
                                <span className={classes.sortDropdown}>
                                    <span id="devProfileSort">Sort: </span>
                                    <SelectBox
                                        buttonType={ButtonTypes.TEXT_PRIMARY}
                                        options={DeveloperProfileSortOptions}
                                        value={
                                            DeveloperProfileSortOptions.find((option) => option.value === sort) ??
                                            DeveloperProfileSortOptions[0]
                                        }
                                        onChange={(newValue) => {
                                            setSort(newValue.value as DeveloperProfileSort);
                                        }}
                                        describedBy={"devProfileSort"}
                                        renderLeft={false}
                                        horizontalOffset={true}
                                        offsetPadding={true}
                                    />
                                </span>
                                <span className={classes.sortDropdown}>
                                    <span id="trackedFilter">Filter: </span>
                                    <SelectBox
                                        buttonType={ButtonTypes.TEXT_PRIMARY}
                                        options={Object.values(trackedOptions)}
                                        value={trackedOptions[trackedValue]!}
                                        onChange={(newValue) => {
                                            setTrackedValue(newValue.value as any);
                                        }}
                                        describedBy={"trackedFilter"}
                                        renderLeft={false}
                                        horizontalOffset={true}
                                        offsetPadding={true}
                                    />
                                </span>
                                <span className={classes.spacer} />
                                <NumberedPager {...paginationProps} rangeOnly />
                            </div>
                        }
                        pager={<NumberedPager {...paginationProps} onChange={setPage} />}
                    />
                </div>
            }
            contentClassNames={developerProfileClasses().pageContent}
        />
    );
}

function ProfileList(props: {
    query: ReturnType<typeof useDeveloperProfilesQuery>;
    filters: React.ReactNode;
    pager: React.ReactNode;
}) {
    const { filters, query } = props;

    if (query.isLoading) {
        return <Loader />;
    }

    if (query.isError) {
        return <CoreErrorMessages error={query.error as IApiError} />;
    }

    return (
        <PageBox>
            {filters}
            <List>
                {query.data.profiles.length === 0 && <PageBox as="li">No profiles found.</PageBox>}
                {query.data.profiles.map((profile) => {
                    return (
                        <ListItem
                            headingDepth={3}
                            key={profile.developerProfileID}
                            url={DeveloperProfileDetailRoute.url(profile.developerProfileID)}
                            name={profile.name}
                            metas={<DeveloperProfileMetas {...profile} />}
                            actions={<DeveloperProfileOptionsMenu profile={profile} />}
                        ></ListItem>
                    );
                })}
            </List>
            {props.pager}
        </PageBox>
    );
}

export default DeveloperProfilesListPage;
