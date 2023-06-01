/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import React, { useState, useEffect, useMemo, useRef } from "react";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { useDiscussionList } from "@library/features/discussions/discussionHooks";
import { LoadStatus } from "@library/@types/api/core";
import { DiscussionListSortOptions, IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import isEqual from "lodash/isEqual";
import { DiscussionListAssetHeader } from "@library/features/discussions/DiscussionListAssetHeader";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import QuickLinks from "@library/navigation/QuickLinks";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { DiscussionGridView } from "@library/features/discussions/DiscussionGridView";
import { PageBox } from "@library/layout/PageBox";
import { t } from "@vanilla/i18n";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import QueryString from "@library/routing/QueryString";
import { useLocation } from "react-router";
import QueryStringParams from "qs";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import DiscussionListLoader from "@library/features/discussions/DiscussionListLoader";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { ListItemIconPosition } from "@library/lists/ListItem.variables";
import { Widget } from "@library/layout/Widget";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";

interface IProps extends React.ComponentProps<typeof DiscussionListModule> {
    categoryFollowEnabled?: boolean;
    isList?: boolean;
    isPreview?: boolean;
}

const DEFAULT_PAGINATION = {
    currentPage: 1,
    limit: 10,
};

export function DiscussionListAsset(props: IProps) {
    const {
        discussions: discussionsFromProps,
        categoryFollowEnabled,
        title,
        subtitle,
        description,
        containerOptions,
    } = props;
    const selfRef = useRef<HTMLDivElement>(null);
    const location = useLocation();
    const [apiParams, setApiParams] = useState<IGetDiscussionListParams>(props.apiParams ?? {});
    const [pagination, setPagination] = useState<ILinkPages>({});
    const variables = discussionListVariables(props.discussionOptions);

    useEffect(() => {
        const urlParams = QueryStringParams.parse(location.search.substring(1));
        const urlPage = urlParams.page ? parseInt(urlParams.page as string) : 1;
        // if the rendered list is a Layout Editor preview, strictly use the sort
        // from the passed apiParams and not the state or url query sort
        const sort = props.isPreview
            ? props.apiParams.sort
            : ((urlParams.sort ?? apiParams.sort) as DiscussionListSortOptions);
        setApiParams({
            ...apiParams,
            ...urlParams,
            sort,
            page: urlPage,
            pinOrder: getPinOrder(sort),
        });
        setPagination({
            ...pagination,
            currentPage: urlPage,
            limit: apiParams.limit ?? 10,
        });
    }, [props.apiParams, props.isPreview]);

    //if our original apiParams has been changed from front end, we should keep using the changed one
    const preHydratedDiscussions = useMemo(
        () =>
            (isEqual(props.apiParams, apiParams) && isEqual(DEFAULT_PAGINATION, pagination)) || props.isPreview
                ? discussionsFromProps
                : undefined,
        [apiParams, pagination],
    );

    const discussions = useDiscussionList(apiParams, preHydratedDiscussions, pagination);
    const isList = props.isList || containerOptions?.displayType === WidgetContainerDisplayType.LIST;
    const isLink = containerOptions?.displayType === WidgetContainerDisplayType.LINK;

    const contentIsLoaded = discussions && discussions.status === LoadStatus.SUCCESS;
    const noDiscussions =
        contentIsLoaded && discussions.data?.discussionList && discussions.data?.discussionList.length === 0;

    const updateApiParams = (newParams: Partial<IGetDiscussionListParams>) => {
        const page = newParams.page ?? 1;

        setApiParams({
            ...apiParams,
            ...newParams,
            page,
            pinOrder: getPinOrder(newParams.sort || apiParams.sort),
        });

        setPagination({ currentPage: page });

        if (newParams.page) {
            window.scrollTo({ top: selfRef.current?.offsetTop ? selfRef.current?.offsetTop - 10 : 0 });
        }
    };

    const getPinOrder = (sort?) => {
        const tmpSort = sort ?? DiscussionListSortOptions.RECENTLY_COMMENTED;
        const pinMixed = [DiscussionListSortOptions.OLDEST, DiscussionListSortOptions.TOP];
        return pinMixed.includes(tmpSort as DiscussionListSortOptions) ? "mixed" : "first";
    };

    const paginationProps: INumberedPagerProps = {
        totalResults: pagination?.total,
        currentPage: pagination?.currentPage,
        pageLimit: pagination?.limit,
    };
    const assetFooter = <NumberedPager {...paginationProps} onChange={(page: number) => updateApiParams({ page })} />;

    const assetHeader = (
        <DiscussionListAssetHeader
            discussionIDs={discussions.data?.discussionList?.map((discussion) => discussion.discussionID)}
            noCheckboxes={props.noCheckboxes || isLink}
            categoryFollowEnabled={categoryFollowEnabled}
            paginationProps={paginationProps}
            apiParams={apiParams}
            updateApiParams={updateApiParams}
        />
    );

    let loading: React.ReactNode | null = null;
    let error: React.ReactNode | null = null;

    if (discussions.status === LoadStatus.LOADING || discussions.status === LoadStatus.PENDING) {
        loading = (
            <DiscussionListLoader
                count={props.apiParams.limit ?? 10}
                displayType={containerOptions?.displayType}
                containerProps={{
                    ...props,
                    extraHeader: assetHeader,
                }}
                itemOptions={{
                    excerpt: props.discussionOptions?.excerpt?.display,
                    checkbox: !props.noCheckboxes,
                    image: props.discussionOptions?.featuredImage?.display,
                    icon: variables.item.options.iconPosition !== ListItemIconPosition.HIDDEN,
                    iconInMeta: variables.item.options.iconPosition === ListItemIconPosition.META,
                }}
            />
        );
    }

    if (!discussions.data?.discussionList || discussions.status === LoadStatus.ERROR || discussions.error) {
        error = <CoreErrorMessages apiError={discussions.error} />;
    }

    useEffect(() => {
        if (
            discussions.status === LoadStatus.SUCCESS &&
            discussions.data?.pagination &&
            !isEqual(discussions.data?.pagination, pagination)
        ) {
            setPagination(discussions.data?.pagination);
        }
    }, [discussions]);

    const urlQueryString = (
        <QueryString
            value={{
                page: apiParams.page,
                sort: apiParams.sort,
                followed: apiParams.followed,
            }}
            defaults={{
                page: 1,
                sort: DiscussionListSortOptions.RECENTLY_COMMENTED,
                followed: false,
            }}
            syncOnFirstMount
        />
    );

    //for proper loading placeholder etc
    if (error || noDiscussions || isList) {
        return (
            <HomeWidgetContainer
                title={title}
                subtitle={subtitle}
                description={description}
                options={{
                    ...props.containerOptions,
                    isGrid: false,
                    displayType: WidgetContainerDisplayType.LIST,
                }}
                extraHeader={assetHeader}
            >
                <div ref={selfRef}>
                    {urlQueryString}
                    {!contentIsLoaded && (error || loading)}
                    {contentIsLoaded && noDiscussions && <PageBox>{t("No discussions were found.")}</PageBox>}
                    {contentIsLoaded && !noDiscussions && (
                        <DiscussionListView
                            noCheckboxes={props.noCheckboxes}
                            discussions={discussions.data?.discussionList as IDiscussion[]}
                            discussionOptions={props.discussionOptions}
                            disableButtonsInItems={props.disableButtonsInItems}
                        />
                    )}
                    {assetFooter}
                </div>
            </HomeWidgetContainer>
        );
    }

    if (isLink) {
        return (
            <div ref={selfRef}>
                {urlQueryString}
                {contentIsLoaded && !noDiscussions ? (
                    <QuickLinks
                        title={props.title}
                        links={discussions.data?.discussionList?.map((discussion, index) => {
                            return {
                                id: `${index}`,
                                name: discussion.name ?? "",
                                url: discussion.url ?? "",
                            };
                        })}
                        containerOptions={props.containerOptions}
                        extraHeader={assetHeader}
                    />
                ) : (
                    <Widget>
                        <PageHeadingBox
                            title={title}
                            options={{
                                alignment: props.containerOptions?.headerAlignment,
                            }}
                        />
                        {assetHeader}
                        <PageBox>{loading}</PageBox>
                    </Widget>
                )}
                {assetFooter}
            </div>
        );
    }

    return (
        <div ref={selfRef}>
            {urlQueryString}
            {contentIsLoaded && !noDiscussions ? (
                <DiscussionGridView
                    {...props}
                    discussions={discussions.data?.discussionList as IDiscussion[]}
                    assetHeader={assetHeader}
                />
            ) : (
                loading
            )}
            {assetFooter}
        </div>
    );
}

export default DiscussionListAsset;
