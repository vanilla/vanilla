/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import DidThisAnswer from "@QnA/components/DidThisAnswer";
import ViewInContext from "@QnA/components/ViewInContext";
import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PageBox } from "@library/layout/PageBox";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam } from "@library/routing/routingUtils";
import { ITabData, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useCommentListQuery, useCommentThreadQuery } from "@vanilla/addon-vanilla/thread/Comments.hooks";
import { DiscussionCommentsAssetFlat } from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.flat";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { t } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";
import { DiscussionsApi } from "@vanilla/addon-vanilla/thread/DiscussionsApi";
import { DiscussionCommentsAssetNested } from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.nested";
import { hasPermission } from "@library/features/users/Permission";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";

type BaseThreadOrFlat =
    | {
          comments: React.ComponentProps<typeof DiscussionCommentsAssetFlat>["comments"];
          apiParams: React.ComponentProps<typeof DiscussionCommentsAssetFlat>["apiParams"];
          threadStyle: "flat";
      }
    | {
          commentsThread: React.ComponentProps<typeof DiscussionCommentsAssetNested>["commentsThread"];
          apiParams: React.ComponentProps<typeof DiscussionCommentsAssetNested>["apiParams"];
          threadStyle: React.ComponentProps<typeof DiscussionCommentsAssetNested>["threadStyle"];
      };

type IProps = BaseThreadOrFlat & {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;

    acceptedAnswers?: React.ComponentProps<typeof DiscussionCommentsAssetFlat>["comments"];
    acceptedAnswersApiParams?: React.ComponentProps<typeof DiscussionCommentsAssetFlat>["apiParams"];

    rejectedAnswers?: React.ComponentProps<typeof DiscussionCommentsAssetFlat>["comments"];
    rejectedAnswersApiParams?: React.ComponentProps<typeof DiscussionCommentsAssetFlat>["apiParams"];
    containerOptions?: IHomeWidgetContainerOptions;
    defaultTabID?: string;
    tabTitles: {
        all: string;
        accepted: string;
        rejected: string;
    };
};

function TabbedCommentListAsset(props: IProps) {
    const {
        discussion: discussionPreload,
        discussionApiParams,
        apiParams,
        rejectedAnswers: rejectedAnswersPreload,
        acceptedAnswersApiParams,
        acceptedAnswers: acceptedAnswersPreload,
        rejectedAnswersApiParams,
    } = props;

    const { discussionID } = discussionPreload;
    const { query } = useDiscussionQuery(discussionID, discussionApiParams, discussionPreload);
    const discussion = query.data!;

    const acceptedAnswers =
        acceptedAnswersPreload ??
        (!!discussion?.attributes?.question?.acceptedAnswers &&
        (discussion.attributes.question.acceptedAnswers as IComment[]).length > 0
            ? { paging: {}, data: discussion.attributes.question.acceptedAnswers as IComment[] }
            : undefined) ??
        undefined;

    const rejectedAnswers =
        rejectedAnswersPreload ??
        (!!discussion?.attributes?.question?.rejectedAnswers &&
        (discussion.attributes.question.rejectedAnswers as IComment[]).length > 0
            ? { paging: {}, data: discussion.attributes.question.rejectedAnswers as IComment[] }
            : undefined) ??
        undefined;

    const { query: acceptedAnswersQuery } = useCommentListQuery(acceptedAnswersApiParams!, acceptedAnswers);
    const { query: rejectedAnswersQuery } = useCommentListQuery(
        rejectedAnswersApiParams!,
        rejectedAnswers,
        hasPermission("curation.manage"),
    );

    const tabs: ITabData[] = [
        {
            label: t(props.tabTitles.all),
            tabID: "all",
            contents: (
                <>
                    {props.threadStyle === "nested" ? (
                        <DiscussionCommentsAssetNested
                            discussion={discussion}
                            discussionApiParams={discussionApiParams}
                            commentsThread={props.commentsThread}
                            apiParams={props.apiParams}
                            renderTitle={false}
                            threadStyle="nested"
                            ThreadItemActionsComponent={DidThisAnswer}
                            containerOptions={props.containerOptions}
                        />
                    ) : (
                        <DiscussionCommentsAssetFlat
                            discussion={discussion}
                            discussionApiParams={discussionApiParams}
                            comments={props.comments}
                            apiParams={props.apiParams}
                            renderTitle={false}
                            threadStyle="flat"
                            ThreadItemActionsComponent={DidThisAnswer}
                            containerOptions={props.containerOptions}
                        />
                    )}
                </>
            ),
        },
    ]
        .concat(
            acceptedAnswersQuery?.data?.data?.length ?? 0 > 0
                ? [
                      {
                          label: t(props.tabTitles.accepted),
                          tabID: "accepted",
                          contents: (
                              <DiscussionCommentsAssetFlat
                                  discussion={discussion}
                                  discussionApiParams={discussionApiParams}
                                  comments={acceptedAnswersQuery?.data}
                                  apiParams={acceptedAnswersApiParams!}
                                  renderTitle={false}
                                  threadStyle="flat"
                                  ThreadItemActionsComponent={ViewInContext}
                                  containerOptions={props.containerOptions}
                              />
                          ),
                      },
                  ]
                : [],
        )
        .concat(
            rejectedAnswersQuery?.data?.data?.length ?? 0 > 0
                ? [
                      {
                          label: t(props.tabTitles.rejected),
                          tabID: "rejected",
                          contents: (
                              <DiscussionCommentsAssetFlat
                                  discussion={discussion}
                                  discussionApiParams={discussionApiParams}
                                  comments={rejectedAnswersQuery?.data}
                                  apiParams={rejectedAnswersApiParams!}
                                  renderTitle={false}
                                  threadStyle="flat"
                                  ThreadItemActionsComponent={ViewInContext}
                                  containerOptions={props.containerOptions}
                              />
                          ),
                      },
                  ]
                : [],
        );
    const tabIDs = tabs.map((t) => t.tabID);
    const isCommentUrl = window.location.href.includes("/comment/");

    // If the user followed a link to a comment, default to the "all" tab
    // Otherwise default to the "accepted" tab if there are accepted answers, "all" if not
    const defaultTabID =
        props.defaultTabID ??
        (!isCommentUrl && !!acceptedAnswers?.data?.length && acceptedAnswers.data.length > 0 ? "accepted" : "all");

    // If a tab is specified, use that, otherwise use the default defined above
    const queryTab = useQueryParam("tab", defaultTabID);

    const [selectedTabIndex, _setSelectedTabIndex] = useState(
        Math.max(
            tabIDs.findIndex((t) => t === queryTab),
            0,
        ),
    );

    function setSelectedTabIndex(index: number) {
        const newTabIndex = index >= 0 && index < tabs.length ? index : 0;
        _setSelectedTabIndex(newTabIndex);
    }

    useEffect(() => {
        setSelectedTabIndex(selectedTabIndex);
    }, [tabs.length]);

    useQueryStringSync(
        {
            tab: tabIDs[selectedTabIndex],
        },
        {},
        true,
        true,
    );

    return (
        <>
            {discussion.countComments > 0 && (
                <Tabs
                    key={tabs.length}
                    largeTabs
                    tabType={TabsTypes.BROWSE}
                    data={tabs}
                    activeTab={selectedTabIndex}
                    setActiveTab={setSelectedTabIndex}
                    extendContainer
                />
            )}
        </>
    );
}

export default TabbedCommentListAsset;
