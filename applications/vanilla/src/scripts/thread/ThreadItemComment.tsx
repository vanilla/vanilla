/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import ModalConfirm from "@library/modal/ModalConfirm";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { DRAFT_CONTENT_KEY, DRAFT_PARENT_ID_KEY } from "@vanilla/addon-vanilla/thread/components/ThreadCommentEditor";
import { ThreadItemMobileReply } from "@vanilla/addon-vanilla/thread/ThreadItemMobileReply";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/thread/NestedComments.classes";
import { PartialCommentsList } from "@vanilla/addon-vanilla/thread/NestedCommentsList";
import { isThreadHole } from "@vanilla/addon-vanilla/thread/threadUtils";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { getLocalStorageOrDefault, useMeasure, useMobile } from "@vanilla/react-utils";
import { useCallback, useRef, useState } from "react";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

interface IProps {
    threadItem: IThreadItem & { type: "comment" };
    discussion: IDiscussion;
    showOPTag?: boolean;
    isPreview?: boolean;
}

/**
 * Renders a comment thread item with children
 */
export function ThreadItemComment(props: IProps) {
    const { threadItem } = props;
    const [isLoading, setIsLoading] = useState(false);
    const {
        discussion,
        getComment,
        updateComment,
        addToThread,
        collapseThreadAtPath,
        lastChildRefsByID,
        threadDepthLimit,
        currentReplyFormPath,
        showReplyForm,
        switchReplyForm,
    } = useCommentThread();
    const { hasPermission } = usePermissionsContext();
    const canReply = hasPermission("comments.add");
    const comment = getComment(props.threadItem.commentID);
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
            return 20;
        }
        return roundedHeight + 4;
    }, [childrenMeasure.height, lastChildRefsByID, threadItem.commentID]);

    const hasHole = props.threadItem.children && props.threadItem.children.some((child) => child.type === "hole");
    const hasOnlyHole = hasHole && props.threadItem.children?.length === 1;

    const descenderButtonContent = hasOnlyHole ? <Icon icon={"analytics-add"} /> : <Icon icon={"analytics-remove"} />;

    const descenderButtonAction = () => {
        setIsLoading(true);
        if (hasOnlyHole) {
            // Should find the hole and update the thread
            const hole = props.threadItem.children?.find((child) => child.type === "hole");
            hole && isThreadHole(hole) && addToThread(hole.apiUrl, hole.path).then(() => setIsLoading(false));
        } else {
            // Should collapse the children
            collapseThreadAtPath(props.threadItem.path).then(() => setIsLoading(false));
        }
    };

    const isMobile = useMobile();
    const [isMobileReplyModalVisible, setIsMobileReplyModalVisible] = useState(false);

    const draftContent = getLocalStorageOrDefault(
        `${DRAFT_CONTENT_KEY}-${discussion.discussionID}`,
        JSON.stringify(EMPTY_RICH2_BODY),
        true,
    );

    const draftParentID = getLocalStorageOrDefault(`${DRAFT_PARENT_ID_KEY}-${discussion.discussionID}`, -1, true);
    const hasActiveDraft = draftContent !== JSON.stringify(EMPTY_RICH2_BODY);
    const isDraftForThisComment = draftParentID === props.threadItem.commentID;

    const handleReply = () => {
        // Mobile reply forms are not added to the thread structure at all
        if (isMobile) {
            if (hasActiveDraft && !isDraftForThisComment) {
                setDiscardDraftModalVisible(true);
            } else {
                setIsMobileReplyModalVisible(true);
            }
        } else {
            // If there are no active reply forms, just open one
            if (!currentReplyFormPath) {
                showReplyForm(props.threadItem);
            } else {
                if (hasActiveDraft) {
                    // If there is an active draft, check if it is for this comment
                    if (!isDraftForThisComment) {
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

    const commentThreadItemProps = {
        boxOptions: { borderType: BorderType.NONE },
        discussion: props.discussion,
        showOPTag: props.showOPTag,
        isPreview: props.isPreview,
        ...(props.threadItem.depth <= threadDepthLimit &&
            canReply && {
                onReply: () => handleReply(),
            }),
        onMutateSuccess: async () => {
            updateComment(props.threadItem.commentID);
        },
        hasActiveDraft: hasActiveDraft && isDraftForThisComment,
    };

    const onlyHasReply =
        props.threadItem.children &&
        props.threadItem.children.length === 1 &&
        props.threadItem.children[0].type === "reply";
    const hasChildren = props.threadItem.children && props.threadItem.children.length > 0;

    return (
        <>
            {comment && (
                <>
                    {threadItem.depth <= 1 ? (
                        <>
                            <CommentThreadItem comment={comment} {...commentThreadItemProps} />
                        </>
                    ) : (
                        <>
                            <div className={classes.childCommentItem}>
                                <CommentThreadItem comment={comment} {...commentThreadItemProps} />
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
            <ThreadItemMobileReply
                discussion={props.discussion}
                threadItem={props.threadItem}
                visibility={isMobileReplyModalVisible}
                onVisibilityChange={setIsMobileReplyModalVisible}
            />
            <ModalConfirm
                isVisible={discardDraftModalVisible}
                title={t("Discard Draft")}
                onCancel={() => setDiscardDraftModalVisible(false)}
                onConfirm={() => {
                    isMobile ? setIsMobileReplyModalVisible(true) : switchReplyForm(props.threadItem);
                    setDiscardDraftModalVisible(false);
                }}
                confirmTitle={t("Discard")}
                confirmClasses={classes.warningModalConfirm}
            >
                <div className={classes.warningModalContent}>
                    <p>{t("You have an unposted draft, replying to this comment will discard your draft.")}</p>
                    <p className={classes.warningEmphasis}>{t("Are you sure you want to discard it?")}</p>
                </div>
            </ModalConfirm>
        </>
    );
}
