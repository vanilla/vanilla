/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IThreadItem, IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { cx } from "@emotion/css";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { ThreadItemHole } from "@vanilla/addon-vanilla/thread/ThreadItemHole";
import { ThreadItemComment } from "@vanilla/addon-vanilla/thread/ThreadItemComment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useRef, useEffect, useState, useMemo } from "react";
import {
    getDraftParentIDAndPath,
    getThreadItemByMatchingPathOrID,
    getThreadItemID,
} from "@vanilla/addon-vanilla/thread/threadUtils";
import { ThreadItemReply } from "@vanilla/addon-vanilla/thread/ThreadItemReply";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { IComment } from "@dashboard/@types/api/comment";
import { IDraftProps } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { DiscussionsApi } from "@vanilla/addon-vanilla/thread/DiscussionsApi";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { scrollToElement } from "@library/content/hashScrolling";
import { t } from "@vanilla/i18n";
import { useToast } from "@library/features/toaster/ToastContext";
import LinkAsButton from "@library/routing/LinkAsButton";

interface IProps extends IThreadResponse {
    discussion: IDiscussion;
    rootClassName?: string;
    discussionApiParams?: DiscussionsApi.GetParams;
    comments?: IWithPaging<IComment[]>;
    commentApiParams?: CommentsApi.IndexThreadParams;
    renderTitle?: boolean;
    draft?: IDraftProps;
    showOPTag?: boolean;
    isPreview?: boolean;
    forceShowDraftToast?: boolean; // storybook purposes
}

/**
 * Renders nested comment list
 */
export function NestedCommentsList(props: Partial<IProps>) {
    const {
        addToThread,
        collapsedThreadPartialsByPath,
        showReplyForm,
        currentReplyFormRef,
        threadStructure,
        visibleReplyFormRef,
        discussion,
    } = useCommentThread();

    const toast = useToast();

    const draftParentIDAndPath = useMemo(() => {
        return getDraftParentIDAndPath(discussion.discussionID);
    }, [threadStructure, discussion.discussionID]);

    const [draftToastID, setDraftToastID] = useState<string | null>(null);

    const createOrUpdateDraftToast = () => {
        draftToastID
            ? toast.updateToast(draftToastID, draftToastContent, true)
            : setDraftToastID(toast.addToast(draftToastContent));
    };

    const matchingCollapsedHolePaths = Object.keys(collapsedThreadPartialsByPath ?? {})
        .filter((holePath) => draftParentIDAndPath?.path.toString().includes(holePath))
        .sort((a, b) => a.length - b.length);

    const matchingThreadPartial =
        collapsedThreadPartialsByPath?.[matchingCollapsedHolePaths[matchingCollapsedHolePaths.length - 1] ?? ""];

    const threadItemWithDraft = props.forceShowDraftToast
        ? threadStructure[0]
        : getThreadItemByMatchingPathOrID(matchingThreadPartial ?? threadStructure, draftParentIDAndPath?.path ?? "");

    const draftToastContent = {
        body: (
            <>
                <span>{t("Saved Draft Available.")}</span>{" "}
                {threadItemWithDraft || visibleReplyFormRef?.current ? (
                    <Button
                        onClick={() => {
                            if (matchingCollapsedHolePaths.length) {
                                matchingCollapsedHolePaths.forEach((matchingCollapsedHolePath) => {
                                    addToThread(matchingCollapsedHolePath, matchingCollapsedHolePath);
                                });
                            }
                            threadItemWithDraft && !currentReplyFormRef?.current && showReplyForm(threadItemWithDraft);

                            // wait a bit so we expand the hole before scrolling, if it was collapsed
                            setTimeout(() => {
                                const draftToScrollTo =
                                    visibleReplyFormRef?.current ??
                                    document.getElementById(`Comment_${draftParentIDAndPath?.parentCommentID}`);
                                draftToScrollTo && scrollToElement(draftToScrollTo, 100);
                            }, 100);
                        }}
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                    >
                        {t("Keep Editing")}
                    </Button>
                ) : (
                    <LinkAsButton
                        to={`/discussion/comment/${draftParentIDAndPath?.parentCommentID}?hasDraft=true#Comment_${draftParentIDAndPath?.parentCommentID}`}
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                    >
                        {t("Navigate to Draft")}
                    </LinkAsButton>
                )}
            </>
        ),
    };

    useEffect(() => {
        // either we force to show the draft taost for storybook purposes
        // or we show the draft toast if there is a draft and the reply form is not visible
        if (
            props.forceShowDraftToast ||
            (!visibleReplyFormRef?.current && getDraftParentIDAndPath(discussion.discussionID)?.parentCommentID)
        ) {
            createOrUpdateDraftToast();
            // we posted the draft, remove the toast
        } else if (!draftParentIDAndPath?.parentCommentID && draftToastID) {
            toast.removeToast(draftToastID);
            setDraftToastID(null);
        }
    }, [draftParentIDAndPath, threadStructure, props.forceShowDraftToast]);

    useEffect(() => {
        const intersectionObserver = new IntersectionObserver(
            (entries: IntersectionObserverEntry[]) => {
                // hide the draft toast if there is one open
                if (entries[0].isIntersecting) {
                    draftToastID && toast.updateToast(draftToastID, draftToastContent, false);
                    // create or update the draft and show it
                } else if (visibleReplyFormRef?.current) {
                    createOrUpdateDraftToast();
                }
            },
            { rootMargin: "-100px 0px 0px 0px" },
        );
        if (visibleReplyFormRef?.current) {
            intersectionObserver.observe(visibleReplyFormRef?.current);
        }
        return () => {
            intersectionObserver.disconnect();
        };
    }, [visibleReplyFormRef, draftToastID]);

    return (
        <>
            <PartialCommentsList discussion={props.discussion} isPreview={props.isPreview} />
        </>
    );
}

/**
 * Renders a list of comments, holes and replies. Will recurse if there are any child comments
 */
export function PartialCommentsList(props: Partial<IProps>) {
    const { threadStructure, discussion, addLastChildRefID, showOPTag } = useCommentThread();
    const thread = props.threadStructure ?? threadStructure;
    const parentRecord = props.discussion ?? discussion;

    const lastChildRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (lastChildRef && lastChildRef.current) {
            const id = lastChildRef.current.getAttribute("data-id");
            if (id) {
                addLastChildRefID(id, lastChildRef);
            }
        }
    }, [thread, lastChildRef]);

    return (
        <>
            {thread.map((threadItem: IThreadItem, index) => {
                const id = getThreadItemID(threadItem);
                const key = `${threadItem.parentCommentID}${id}`;
                const isLast = index === thread.length - 1;
                const refProps = {
                    ...(isLast && { ref: lastChildRef }),
                };

                return (
                    <PageBox
                        key={key}
                        options={{ borderType: threadItem.depth <= 1 ? BorderType.SEPARATOR : BorderType.NONE }}
                    >
                        <div
                            className={cx(props.rootClassName, threadItem.type)}
                            data-depth={threadItem.depth}
                            data-id={threadItem.parentCommentID}
                            {...refProps}
                        >
                            {threadItem.type === "comment" && (
                                <ThreadItemComment
                                    threadItem={threadItem}
                                    discussion={parentRecord}
                                    showOPTag={showOPTag}
                                    isPreview={props.isPreview}
                                />
                            )}
                            {threadItem.type === "hole" && <ThreadItemHole threadItem={threadItem} />}
                            {threadItem.type === "reply" && <ThreadItemReply threadItem={threadItem} />}
                        </div>
                    </PageBox>
                );
            })}
        </>
    );
}
