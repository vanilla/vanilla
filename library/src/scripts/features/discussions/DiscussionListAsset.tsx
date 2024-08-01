/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import React, { useState, useEffect, useMemo, useRef } from "react";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { DISCUSSIONS_MAX_PAGE_COUNT, useDiscussionList } from "@library/features/discussions/discussionHooks";
import { LoadStatus } from "@library/@types/api/core";
import { DiscussionListSortOptions, IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import isEqual from "lodash-es/isEqual";
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
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import DiscussionListLoader from "@library/features/discussions/DiscussionListLoader";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { ListItemIconPosition } from "@library/lists/ListItem.variables";
import { Widget } from "@library/layout/Widget";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { usePermissionsContext } from "../users/PermissionsContext";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Message from "@library/messages/Message";
import { Icon } from "@vanilla/icons";
import { useLocation } from "react-router";

interface IProps extends React.ComponentProps<typeof DiscussionListModule> {
    categoryFollowEnabled?: boolean;
    isList?: boolean;
    isPreview?: boolean;
    defaultSort?: DiscussionListSortOptions;
}

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
    const [apiParams, setApiParams] = useState<IGetDiscussionListParams>(props.apiParams ?? {});
    const [pagination, setPagination] = useState<ILinkPages>(props.initialPaging ?? {});
    const variables = discussionListVariables(props.discussionOptions);
    const { hasPermission } = usePermissionsContext();
    const [permissionError, setPermissionError] = useState<boolean>(false);
    const location = useLocation();

    const isCommunityManager = hasPermission("community.manage");

    const actualApiParams = useMemo(() => {
        // if the rendered list is a Layout Editor preview, strictly use the sort
        // from the passed apiParams and not the state or url query sort
        const sort = apiParams.sort as DiscussionListSortOptions;

        const finalParams = {
            ...apiParams,
            sort,
            pinOrder: getPinOrder(sort),
        };

        // we always receive this from props, so reset it here to avoid categoryID from previous page/state if navigating between react pages
        if (props.apiParams?.categoryID) {
            finalParams.categoryID = props.apiParams?.categoryID;
        }

        // In case a community manager shared a link that had one of these set.
        // Display a permission error and exclude the parameter.
        if ((finalParams.internalStatusID?.length || finalParams.hasComments !== undefined) && !isCommunityManager) {
            delete finalParams.internalStatusID;
            delete finalParams.hasComments;
            setPermissionError(true);
        }

        return finalParams;
    }, [props.apiParams, apiParams, props.isPreview]);

    //if our original apiParams has been changed from front end, we should keep using the changed one
    const preHydratedDiscussions = useMemo(
        () => (isEqual(props.apiParams, actualApiParams) || props.isPreview ? discussionsFromProps : undefined),
        [actualApiParams, pagination],
    );

    const discussions = useDiscussionList(actualApiParams, preHydratedDiscussions, pagination);
    const isList = props.isList || containerOptions?.displayType === WidgetContainerDisplayType.LIST;
    const isLink = containerOptions?.displayType === WidgetContainerDisplayType.LINK;

    const contentIsLoaded = discussions && discussions.status === LoadStatus.SUCCESS;
    const noDiscussions =
        contentIsLoaded && discussions.data?.discussionList && discussions.data?.discussionList.length === 0;

    const updateApiParams = (newParams: Partial<IGetDiscussionListParams>) => {
        const page = newParams.page || 1;
        const sort = newParams.sort || actualApiParams.sort;

        const tmpParams = {
            ...apiParams,
            ...newParams,
            page,
            pinOrder: getPinOrder(sort),
        };
        setApiParams(tmpParams);

        if (newParams.page) {
            window.scrollTo({ top: selfRef.current?.offsetTop ? selfRef.current?.offsetTop - 10 : 0 });
        }
    };

    const paginationProps: INumberedPagerProps = {
        totalResults: pagination?.total,
        currentPage: pagination?.currentPage,
        pageLimit: pagination?.limit,
        hasMorePages: pagination?.total ? pagination?.total >= DISCUSSIONS_MAX_PAGE_COUNT : false,
    };
    const assetFooter = <NumberedPager {...paginationProps} onChange={(page: number) => updateApiParams({ page })} />;

    const assetHeader = (
        <DiscussionListAssetHeader
            discussionIDs={discussions.data?.discussionList?.map((discussion) => discussion.discussionID)}
            noCheckboxes={props.noCheckboxes || isLink}
            categoryFollowEnabled={categoryFollowEnabled}
            paginationProps={paginationProps}
            apiParams={actualApiParams}
            updateApiParams={updateApiParams}
            isPreview={props.isPreview}
        />
    );

    let loading: React.ReactNode | null = null;
    let error: React.ReactNode | null = null;

    if (discussions.status === LoadStatus.LOADING || discussions.status === LoadStatus.PENDING) {
        loading = (
            <DiscussionListLoader
                count={(actualApiParams.limit as number) ?? 10}
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

    // this bit is when we are on a category page via legacy url/path with page number, we strip out the old pagination and stick with the new one in url
    useEffect(() => {
        if (
            props.apiParams.layoutViewType === "categoryList" &&
            ((props.apiParams.categoryUrlCode &&
                location.pathname.includes(`categories/${props.apiParams.categoryUrlCode}/p`)) ||
                (props.apiParams.categoryID &&
                    location.pathname.includes(`categories/${props.apiParams.categoryID.toString()}/p`)))
        ) {
            const fullUrl = window.location.href;
            const newUrl = fullUrl.split("/p")[0];
            window.history.replaceState(null, "", newUrl);
        }
    }, []);

    useEffect(() => {
        if (
            discussions.status === LoadStatus.SUCCESS &&
            discussions.data?.pagination &&
            !isEqual(discussions.data?.pagination, pagination)
        ) {
            setPagination(discussions.data?.pagination);
        }
    }, [discussions]);

    const queryParams = {
        type: actualApiParams.type,
        tagID: actualApiParams.tagID,
        statusID: actualApiParams.statusID,
        internalStatusID: actualApiParams.internalStatusID,
        hasComments: actualApiParams.hasComments,
        page: actualApiParams.page,
        followed: actualApiParams.followed,
        sort: actualApiParams.sort,
    };
    const urlQueryString = (
        <QueryString
            value={queryParams}
            defaults={{
                page: 1,
                followed: false,
                sort: props.defaultSort,
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
                    {contentIsLoaded && noDiscussions && (
                        <PageBox options={{ borderType: BorderType.SEPARATOR }}>
                            {t("No discussions were found.")}
                        </PageBox>
                    )}
                    {contentIsLoaded && !noDiscussions && (
                        <>
                            {permissionError && (
                                <Message
                                    stringContents={t(
                                        "You do not have permission to access one or more filters in the provided link.",
                                    )}
                                    type="warning"
                                    onConfirm={() => setPermissionError(false)}
                                    icon={<Icon icon="status-warning" />}
                                />
                            )}
                            <DiscussionListView
                                noCheckboxes={props.noCheckboxes}
                                discussions={discussions.data?.discussionList as IDiscussion[]}
                                discussionOptions={props.discussionOptions}
                                disableButtonsInItems={props.disableButtonsInItems}
                            />
                        </>
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

function getPinOrder(sort?: string): IGetDiscussionListParams["pinOrder"] {
    const tmpSort = sort ?? DiscussionListSortOptions.RECENTLY_COMMENTED;
    const pinMixed = [DiscussionListSortOptions.OLDEST, DiscussionListSortOptions.TOP];
    return pinMixed.includes(tmpSort as DiscussionListSortOptions) ? "mixed" : "first";
}

export default DiscussionListAsset;
