/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import DidThisAnswer from "@QnA/components/DidThisAnswer";
import ViewInContext from "@QnA/components/ViewInContext";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PageBox } from "@library/layout/PageBox";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam } from "@library/routing/routingUtils";
import { ITabData, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { BorderType } from "@library/styles/styleHelpersBorders";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { t } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    comments: React.ComponentProps<typeof DiscussionCommentsAsset>["comments"];
    apiParams: React.ComponentProps<typeof DiscussionCommentsAsset>["apiParams"];

    acceptedAnswers?: React.ComponentProps<typeof DiscussionCommentsAsset>["comments"];
    acceptedAnswersApiParams?: React.ComponentProps<typeof DiscussionCommentsAsset>["apiParams"];

    rejectedAnswers?: React.ComponentProps<typeof DiscussionCommentsAsset>["comments"];
    rejectedAnswersApiParams?: React.ComponentProps<typeof DiscussionCommentsAsset>["apiParams"];
}

function TabbedCommentListAsset(props: IProps) {
    const {
        discussion: discussionPreload,
        discussionApiParams,
        comments,
        apiParams,
        rejectedAnswers: rejectedAnswersPreload,
        acceptedAnswersApiParams,
        acceptedAnswers: acceptedAnswersPreload,
        rejectedAnswersApiParams,
    } = props;

    const { discussionID } = discussionPreload;

    const { query } = useDiscussionQuery(discussionID, discussionApiParams, discussionPreload);

    const discussion = query.data!;

    const acceptedAnswers = discussion.attributes?.question?.acceptedAnswers ?? acceptedAnswersPreload?.data ?? [];
    const rejectedAnswers = discussion.attributes?.question?.rejectedAnswers ?? rejectedAnswersPreload?.data ?? [];

    const tabs: ITabData[] = [
        {
            label: t("All Comments"),
            tabID: "all",
            contents: (
                <DiscussionCommentsAsset
                    discussion={discussion}
                    discussionApiParams={discussionApiParams}
                    comments={comments}
                    apiParams={apiParams}
                    renderTitle={false}
                    ThreadItemActionsComponent={DidThisAnswer}
                />
            ),
        },
    ]
        .concat(
            acceptedAnswers.length > 0
                ? [
                      {
                          label: t("Accepted Answers"),
                          tabID: "accepted",
                          contents: (
                              <DiscussionCommentsAsset
                                  discussion={discussion}
                                  discussionApiParams={discussionApiParams}
                                  comments={acceptedAnswersPreload}
                                  apiParams={acceptedAnswersApiParams!}
                                  renderTitle={false}
                                  ThreadItemActionsComponent={ViewInContext}
                              />
                          ),
                      },
                  ]
                : [],
        )
        .concat(
            rejectedAnswers.length > 0
                ? [
                      {
                          label: t("Rejected Answers"),
                          tabID: "rejected",
                          contents: (
                              <DiscussionCommentsAsset
                                  discussion={discussion}
                                  discussionApiParams={discussionApiParams}
                                  comments={rejectedAnswersPreload}
                                  apiParams={rejectedAnswersApiParams!}
                                  renderTitle={false}
                                  ThreadItemActionsComponent={ViewInContext}
                              />
                          ),
                      },
                  ]
                : [],
        );

    const tabIDs = tabs.map((t) => t.tabID);
    const defaultTabID = acceptedAnswers.length > 0 ? "accepted" : "all";
    const queryTab = useQueryParam("tab", defaultTabID);

    const [selectedTabIndex, setSelectedTabIndex] = useState(
        Math.max(
            tabIDs.findIndex((t) => t === queryTab),
            0,
        ),
    );
    useEffect(() => {
        const initialTabIndex = Math.max(
            tabIDs.findIndex((t) => t === queryTab),
            0,
        );
        setSelectedTabIndex(initialTabIndex);
    }, [queryTab]);

    useQueryStringSync(
        {
            tab: tabIDs[selectedTabIndex],
        },
        {},
        true,
    );

    return (
        <>
            {discussion.countComments > 0 && (
                <PageBox
                    options={{
                        borderType: BorderType.SEPARATOR,
                    }}
                >
                    <Tabs
                        largeTabs
                        tabType={TabsTypes.BROWSE}
                        data={tabs}
                        activeTab={selectedTabIndex}
                        setActiveTab={setSelectedTabIndex}
                        extendContainer
                    />
                </PageBox>
            )}
        </>
    );
}

export default TabbedCommentListAsset;
