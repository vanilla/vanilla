/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import { scrollToCurrentHash } from "@library/content/hashScrolling";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import {
    IHomeWidgetContainerOptions,
    WidgetContainerDisplayType,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { PageBox } from "@library/layout/PageBox";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { List } from "@library/lists/List";
import { Tag } from "@library/metas/Tags";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { Variables } from "@library/styles/Variables";
import { UseQueryResult } from "@tanstack/react-query";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { IDraftProps } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { DiscussionsApi } from "@vanilla/addon-vanilla/thread/DiscussionsApi";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { DiscussionThreadContextProvider } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { t } from "@vanilla/i18n";
import { useLayoutEffect } from "react";
import { useLocation } from "react-router";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    draft?: IDraftProps;
    title?: string;
    showOPTag?: boolean;
    containerOptions?: IHomeWidgetContainerOptions;
    defaultSort?: CommentsApi.IndexThreadParams["sort"];
    isPreview?: boolean;
    children?: React.ReactNode;
    topPager?: React.ReactNode;
    bottomPager?: React.ReactNode;
    ThreadItemActionsComponent?: React.ComponentType<{
        comment: IComment;
        discussion: IDiscussion;
        onMutateSuccess?: () => Promise<void>;
    }>;
    renderTitle?: boolean;
    hasComments?: boolean;
}

export function DiscussionCommentsAssetCommon(props: IProps) {
    const classes = discussionThreadClasses();

    const { getCalcedHashOffset } = useScrollOffset();
    const hash = useLocation().hash;
    useLayoutEffect(() => {
        scrollToCurrentHash(getCalcedHashOffset());
    }, [hash]);

    const hasOutline = Variables.boxHasOutline(
        Variables.box({
            background: props.containerOptions?.innerBackground,
            borderType: props.containerOptions?.borderType,
        }),
    );

    const renderTitle = props.renderTitle ?? true;

    return (
        <DiscussionThreadContextProvider discussion={props.discussion}>
            <HomeWidgetContainer
                depth={2}
                title={
                    renderTitle ? (
                        <>
                            <span>{props.title ?? t("Comments")}</span>{" "}
                            {props.discussion.closed && (
                                <Tag className={classes.closedTag} preset={discussionListVariables().labels.tagPreset}>
                                    {t("Closed")}
                                </Tag>
                            )}
                        </>
                    ) : undefined
                }
                options={{
                    ...props.containerOptions,
                    isGrid: false,
                }}
                extraHeader={props.hasComments && props.topPager}
            >
                {props.hasComments ? (
                    <List
                        options={{
                            box: {
                                borderType:
                                    props.containerOptions?.borderType ??
                                    (hasOutline ? BorderType.NONE : BorderType.SEPARATOR),
                                background: props.containerOptions?.innerBackground,
                            },
                            itemBox: {
                                borderType: BorderType.SEPARATOR,
                            },
                        }}
                    >
                        {props.children}
                    </List>
                ) : (
                    <em>{t("Add a comment to this post.")}</em>
                )}

                {props.discussion.closed && (
                    <PageBox options={{ borderType: BorderType.SEPARATOR_BETWEEN }}>
                        <div
                            className={css({
                                marginTop: 8,
                                marginBottom: 8,
                            })}
                        >
                            {t("This discussion has been closed.")}
                        </div>
                    </PageBox>
                )}
                {props.bottomPager}
            </HomeWidgetContainer>
        </DiscussionThreadContextProvider>
    );
}
