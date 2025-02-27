/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import DidThisAnswer from "@QnA/components/DidThisAnswer";
import ViewInContext from "@QnA/components/ViewInContext";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam } from "@library/routing/routingUtils";
import { ITabData, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { useCommentListQuery } from "@vanilla/addon-vanilla/comments/Comments.hooks";
import { CommentThreadAssetFlat } from "@vanilla/addon-vanilla/comments/CommentThreadAsset.flat";
import { t } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { CommentThreadAssetNested } from "@vanilla/addon-vanilla/comments/CommentThreadAsset.nested";
import { css } from "@emotion/css";

type BaseThreadOrFlat =
    | {
          comments: React.ComponentProps<typeof CommentThreadAssetFlat>["comments"];
          apiParams: React.ComponentProps<typeof CommentThreadAssetFlat>["apiParams"];
          threadStyle: "flat";
      }
    | {
          commentsThread: React.ComponentProps<typeof CommentThreadAssetNested>["commentsThread"];
          apiParams: React.ComponentProps<typeof CommentThreadAssetNested>["apiParams"];
          threadStyle: "nested";
      };

type IProps = BaseThreadOrFlat & {
    acceptedAnswers?: React.ComponentProps<typeof CommentThreadAssetFlat>["comments"];
    acceptedAnswersApiParams?: React.ComponentProps<typeof CommentThreadAssetFlat>["apiParams"];

    rejectedAnswers?: React.ComponentProps<typeof CommentThreadAssetFlat>["comments"];
    rejectedAnswersApiParams?: React.ComponentProps<typeof CommentThreadAssetFlat>["apiParams"];
    containerOptions?: IHomeWidgetContainerOptions;
    defaultTabID?: string;
    tabTitles: {
        all: string;
        accepted: string;
        rejected: string;
    };
};

export default function AnswerThreadAsset(props: IProps) {
    const { rejectedAnswers, acceptedAnswersApiParams, acceptedAnswers, rejectedAnswersApiParams } = props;

    const { hasPermission } = usePermissionsContext();

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
                        <CommentThreadAssetNested
                            {...props}
                            commentsThread={props.commentsThread}
                            apiParams={props.apiParams}
                            renderTitle={false}
                            CommentActionsComponent={DidThisAnswer}
                            containerOptions={props.containerOptions}
                        />
                    ) : (
                        <CommentThreadAssetFlat
                            {...props}
                            comments={props.comments}
                            apiParams={props.apiParams}
                            renderTitle={false}
                            CommentActionsComponent={DidThisAnswer}
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
                              <CommentThreadAssetFlat
                                  {...props}
                                  comments={acceptedAnswersQuery?.data}
                                  apiParams={acceptedAnswersApiParams!}
                                  renderTitle={false}
                                  CommentActionsComponent={ViewInContext}
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
                              <CommentThreadAssetFlat
                                  {...props}
                                  comments={rejectedAnswersQuery?.data}
                                  apiParams={rejectedAnswersApiParams!}
                                  renderTitle={false}
                                  CommentActionsComponent={ViewInContext}
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
        <Tabs
            key={tabs.length}
            largeTabs
            tabType={TabsTypes.BROWSE}
            data={tabs}
            activeTab={selectedTabIndex}
            setActiveTab={setSelectedTabIndex}
            extendContainer
            tabsRootClass={classes.root}
        />
    );
}

const classes = {
    root: css({
        marginTop: 12,
    }),
};
