/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/comments/NestedComments.classes";
import { PartialCommentsList } from "@vanilla/addon-vanilla/comments/NestedCommentsList";
import { isNestedHole } from "@vanilla/addon-vanilla/comments/NestedCommentUtils";
import { Icon } from "@vanilla/icons";
import { useMeasure, useMobile } from "@vanilla/react-utils";
import { memo, useCallback, useEffect, useRef, useState, type ComponentProps } from "react";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import type { IThreadItem } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { CommentItem } from "@vanilla/addon-vanilla/comments/CommentItem";
import { CommentThreadMobileReply } from "@vanilla/addon-vanilla/comments/CommentThreadMobileReply";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { isCommentDraftMeta } from "@vanilla/addon-vanilla/drafts/utils";
import { useLocation } from "react-router";
import { DiscardDraftModal } from "@vanilla/addon-vanilla/comments/DiscardDraftModal";
import { useCreateCommentContext } from "@vanilla/addon-vanilla/posts/CreateCommentContext";
import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";

interface IProps {
    threadItem: IThreadItem & { type: "comment" };
    showOPTag?: boolean;
    isPreview?: boolean;
    authorBadges?: {
        display: boolean;
        limit: number;
    };
}

/**
 * Renders a comment thread item with children
 */
export const NestedCommentItem = memo(function NestedCommentItem(props: IProps) {
    const { hash } = useLocation();
    const { threadItem } = props;
    const [isLoading, setIsLoading] = useState(false);
    const {
        getComment,
        updateComment,
        addToThread,
        collapseThreadAtPath,
        lastChildRefsByID,
        threadDepthLimit,
        currentReplyFormRef,
        showReplyForm,
        switchReplyForm,
        CommentActionsComponent,
        constructReplyFromComment,
        removeReplyFromThread,
    } = useNestedCommentContext();
    const { hasPermission } = usePermissionsContext();
    const comment = getComment(props.threadItem.commentID);

    const commentParent = useCommentThreadParentContext();
    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: comment?.parentRecordType,
        resourceID: comment?.parentRecordID ?? null,
    };

    const replyPermission = hasPermission("comments.add", permissionOptions);
    const closePermission = hasPermission("discussions.close", permissionOptions);
    const canReply = commentParent.closed ? closePermission : replyPermission;

    const { draftToRemove, setDraftToRemove } = useCreateCommentContext();
    const classes = nestCommentListClasses();
    const childrenRef = useRef<HTMLDivElement>(null);
    const childrenMeasure = useMeasure(childrenRef);

    const [discardDraftModalVisible, setDiscardDraftModalVisible] = useState(false);

    const offsetHeight = useCallback(() => {
        let lastChildHeight = 100;
        if (lastChildRefsByID[threadItem.commentID]?.current) {
            const lastChildBox = lastChildRefsByID[threadItem.commentID].current?.getBoundingClientRect();
            if (lastChildBox?.height) {
                lastChildHeight = lastChildBox.height + 50;
            }
        }

        // Ensure positive SVG integer
        const roundedHeight = Math.ceil(childrenMeasure?.height - lastChildHeight);
        if (roundedHeight < 0) {
            return 12;
        }
        return roundedHeight + 4;
    }, [childrenMeasure.height, lastChildRefsByID, threadItem.commentID]);

    const hasHole = props.threadItem.children && props.threadItem.children.some((child) => child.type === "hole");
    const hasOnlyHole = hasHole && props.threadItem.children?.length === 1;

    const descenderButtonContent = hasOnlyHole ? <Icon icon={"filter-add"} /> : <Icon icon={"filter-remove"} />;

    const descenderButtonAction = () => {
        setIsLoading(true);
        if (hasOnlyHole) {
            // Should find the hole and update the thread
            const hole = props.threadItem.children?.find((child) => child.type === "hole");
            if (hole && isNestedHole(hole)) {
                void addToThread(hole.apiUrl, hole.path).then(() => setIsLoading(false));
            }
        } else {
            // Should collapse the children
            void collapseThreadAtPath(props.threadItem.path).then(() => setIsLoading(false));
        }
    };

    const isMobile = useMobile();
    const [isMobileReplyModalVisible, setIsMobileReplyModalVisible] = useState(false);

    const { draft, removeDraft } = useDraftContext();

    const hasDraftForThis = () => {
        if (!draft) {
            return false;
        }
        if (draft && draft?.attributes?.draftMeta && isCommentDraftMeta(draft?.attributes.draftMeta)) {
            return draft?.attributes?.draftMeta?.commentParentID === props.threadItem.commentID;
        }
        return false;
    };

    const handleReply = () => {
        const draftForThis = hasDraftForThis();
        // Mobile reply forms are not added to the thread structure at all
        if (isMobile) {
            if (draft && !draftForThis) {
                setDiscardDraftModalVisible(true);
            } else {
                setIsMobileReplyModalVisible(true);
            }
        } else {
            // If there are no active reply forms, just open one
            if (!draft && !currentReplyFormRef?.current) {
                showReplyForm(props.threadItem);
            } else {
                if (draft) {
                    // If there is an active draft, check if it is for this comment
                    if (!draftForThis) {
                        // Preset the warning modal
                        setDiscardDraftModalVisible(true);
                    } else {
                        // Or just open the reply form
                        switchReplyForm(props.threadItem);
                    }
                } else {
                    // Or just open the reply form
                    switchReplyForm(props.threadItem);
                }
            }
        }
    };

    // Automatically open the reply form if there is a draft for this comment
    useEffect(() => {
        if (props.threadItem.hasOwnProperty("path") && hasDraftForThis()) {
            showReplyForm(props.threadItem);
        }
    }, [props.threadItem]);

    // Remove reply form if create comment is opened
    useEffect(() => {
        if (draftToRemove && draftToRemove?.attributes?.draftMeta) {
            // Ensure nested comment draft
            if (isCommentDraftMeta(draftToRemove.attributes.draftMeta)) {
                // Ensure draft is for this parent comment
                if (draftToRemove?.attributes?.draftMeta?.commentParentID === props.threadItem.commentID) {
                    isMobile
                        ? setIsMobileReplyModalVisible(false)
                        : removeReplyFromThread(constructReplyFromComment(props.threadItem), true);
                    setDraftToRemove(null);
                }
            }
        }
    }, [draftToRemove]);

    const commentItemProps: Omit<ComponentProps<typeof CommentItem>, "comment"> = {
        boxOptions: { borderType: BorderType.NONE },
        showOPTag: props.showOPTag,
        authorBadges: props.authorBadges,
        isPreview: props.isPreview,
        ...(props.threadItem.depth <= threadDepthLimit &&
            canReply && {
                onReply: () => handleReply(),
            }),
        onMutateSuccess: async () => {
            updateComment(props.threadItem.commentID);
        },
        actions:
            comment && CommentActionsComponent ? (
                <CommentActionsComponent
                    comment={comment}
                    onMutateSuccess={async () => {
                        updateComment(props.threadItem.commentID);
                    }}
                />
            ) : undefined,
    };

    const onlyHasReply =
        props.threadItem.children &&
        props.threadItem.children.length === 1 &&
        props.threadItem.children[0].type === "reply";
    const hasChildren = props.threadItem.children && props.threadItem.children.length > 0;

    const isPermalinked = hash?.toLowerCase() === `#comment_${comment?.commentID}`;

    return (
        <>
            {comment && (
                <>
                    {threadItem.depth <= 1 ? (
                        <>
                            <CommentItem comment={comment} {...commentItemProps} />
                        </>
                    ) : (
                        <>
                            <div
                                className={
                                    isPermalinked ? classes.childCommentItemPermaLinked : classes.childCommentItem
                                }
                            >
                                <CommentItem comment={comment} {...commentItemProps} />
                            </div>
                        </>
                    )}
                </>
            )}
            <div style={{ height: "100%" }}>
                {hasChildren && (
                    <div className={cx(classes.childContainer)} ref={childrenRef}>
                        <div className={classes.descender}>
                            <Button
                                buttonType={ButtonTypes.ICON}
                                onClick={() => descenderButtonAction()}
                                disabled={onlyHasReply}
                            >
                                {isLoading ? <ButtonLoader /> : descenderButtonContent}
                            </Button>

                            <svg width={2} height={offsetHeight()} className={classes.descenderLine}>
                                <line
                                    stroke={ColorsUtils.colorOut(globalVariables().border.color)}
                                    strokeWidth={2}
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2={offsetHeight()}
                                />
                            </svg>
                        </div>
                        <div className={cx(classes.commentChildren)}>
                            <PartialCommentsList threadStructure={props.threadItem.children} />
                        </div>
                    </div>
                )}
            </div>
            <CommentThreadMobileReply
                threadItem={props.threadItem}
                visibility={isMobileReplyModalVisible}
                onVisibilityChange={setIsMobileReplyModalVisible}
            />
            <DiscardDraftModal
                isVisible={discardDraftModalVisible}
                setVisibility={(visible) => setDiscardDraftModalVisible(visible)}
                onCancel={() => setDiscardDraftModalVisible(false)}
                onConfirm={() => {
                    draft && setDraftToRemove(draft);
                    removeDraft();
                    if (isMobile) {
                        setIsMobileReplyModalVisible(true);
                    } else {
                        currentReplyFormRef?.current
                            ? switchReplyForm(props.threadItem)
                            : showReplyForm(props.threadItem);
                    }
                    setDiscardDraftModalVisible(false);
                }}
            />
        </>
    );
});
