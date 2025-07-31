/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { scrollToElement } from "@library/content/hashScrolling";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import LinkAsButton from "@library/routing/LinkAsButton";
import { DiscardDraftModal } from "@vanilla/addon-vanilla/comments/DiscardDraftModal";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { isCommentDraftMeta } from "@vanilla/addon-vanilla/drafts/utils";
import { t } from "@vanilla/i18n";
import { logDebug, RecordID } from "@vanilla/utils";
import { createContext, MutableRefObject, ReactNode, useContext, useEffect, useRef, useState } from "react";

interface ICreateCommentContext {
    createCommentLocation: "original-post" | "widget" | "none" | null;
    setCreateCommentLocation: (location: "original-post" | "widget" | "none" | null) => void;
    // This is to handle the draft toast when creating comments, both nested and top level
    visibleReplyFormRef?: MutableRefObject<HTMLFormElement | null>;
    setVisibleReplyFormRef?: (ref: MutableRefObject<HTMLFormElement | null>) => void;
    // This is to handle the discard draft modal
    draftToRemove: IDraft | null;
    setDraftToRemove: (draft: IDraft | null) => void;
}

export const CreateCommentContext = createContext<ICreateCommentContext>({
    createCommentLocation: "widget",
    setCreateCommentLocation: () => {},
    visibleReplyFormRef: undefined,
    setVisibleReplyFormRef: () => {},
    draftToRemove: null,
    setDraftToRemove: () => {},
});

export function useCreateCommentContext() {
    return useContext(CreateCommentContext);
}

/**
 * This is some shared state so that comment creation to the
 * original post can appear over multiple locations on a page
 */
export function CreateCommentProvider(props: {
    children: ReactNode;
    parentRecordID: RecordID;
    parentRecordType: string;
}) {
    const { draft, removeDraft } = useDraftContext();

    const [createCommentLocation, setCreateCommentLocation] =
        useState<ICreateCommentContext["createCommentLocation"]>("widget");
    const [visibleReplyFormRef, setVisibleReplyFormRef] = useState<MutableRefObject<HTMLFormElement | null>>();
    const [discardDraftModalVisible, setDiscardDraftModalVisible] = useState<
        ICreateCommentContext["createCommentLocation"] | null
    >(null);
    // This state holds a cache of the last draft before it is removed. Its needed so that either reply from the comment thread or the new comment form determine if it needs to reveal itself because the thread context is within this provider
    const [draftToRemove, setDraftToRemove] = useState<IDraft | null>(null);

    const isTopLevelCommentDraft = draft ? !(draft?.attributes?.draftMeta ?? {}).hasOwnProperty("commentPath") : false;
    const isDraftCommentForOriginalPost = !!(draft && isTopLevelCommentDraft);

    const handleCreateCommentLocation = (location: ICreateCommentContext["createCommentLocation"]) => {
        if (draft) {
            if (isDraftCommentForOriginalPost) {
                setCreateCommentLocation(location);
            } else {
                setDiscardDraftModalVisible(location);
            }
        } else {
            setCreateCommentLocation(location);
        }
    };

    return (
        <CreateCommentContext.Provider
            value={{
                createCommentLocation,
                setCreateCommentLocation: handleCreateCommentLocation,
                visibleReplyFormRef,
                setVisibleReplyFormRef,
                draftToRemove,
                setDraftToRemove,
            }}
        >
            {props.children}
            <DraftToastManager parentRecordType={props.parentRecordType} parentRecordID={props.parentRecordID} />
            <DiscardDraftModal
                isVisible={!!discardDraftModalVisible}
                setVisibility={() => setDiscardDraftModalVisible(null)}
                onCancel={() => setDiscardDraftModalVisible(null)}
                onConfirm={() => {
                    draft && setDraftToRemove(draft);
                    removeDraft();
                    discardDraftModalVisible && setCreateCommentLocation(discardDraftModalVisible);
                    setDiscardDraftModalVisible(null);
                }}
            />
        </CreateCommentContext.Provider>
    );
}

function DraftToastManager(props: {
    forceShowDraftToast?: boolean;
    parentRecordType: string;
    parentRecordID: RecordID;
}) {
    const [draftToastID, setDraftToastID] = useState<string | null>(null);
    const { addToast, updateToast, removeToast } = useToast();
    const { visibleReplyFormRef } = useCreateCommentContext();
    const { getDraftByMatchers } = useDraftContext();

    // Need to preserve the draft toast id across renders so we can remove it on unmount
    const toastIDToRemove = useRef<string | null>(null);

    useEffect(() => {
        toastIDToRemove.current = draftToastID;
    }, [draftToastID]);

    // Check if thread contains a draft
    const matchers = {
        recordType: "comment",
        parentRecordID: props.parentRecordID,
    };
    const matchingDrafts = getDraftByMatchers(matchers);
    const isDraftInThread = matchingDrafts.length > 0;
    const draftInThread = isDraftInThread ? matchingDrafts[0][1] : null;

    const commentMeta =
        draftInThread?.attributes?.draftMeta &&
        isCommentDraftMeta(draftInThread?.attributes?.draftMeta) &&
        draftInThread?.attributes?.draftMeta;

    const isTopLevelDraft = commentMeta && !commentMeta.hasOwnProperty("commentPath");

    const commentParentID = (commentMeta && commentMeta?.commentParentID) ?? false;

    // Either point to the draft or to the new comment form
    const draftLocationLink = isTopLevelDraft
        ? `${window.location.pathname}?hasDraft=true#create-comment`
        : commentParentID && `/discussion/comment/${commentParentID}?hasDraft=true#Comment_${commentParentID}`;

    const scrollActiveDraftIntoView = () => {
        const id =
            commentMeta && commentMeta?.commentParentID ? `Comment_${commentMeta?.commentParentID}` : "create-comment";
        const draftToScrollTo = visibleReplyFormRef?.current ?? document.getElementById(id);
        draftToScrollTo && scrollToElement(draftToScrollTo, 150);
    };

    const draftToastContent = {
        body: (
            <>
                <span>{t("Saved Draft Available.")}</span>{" "}
                {visibleReplyFormRef?.current ? (
                    <Button
                        onClick={() => {
                            scrollActiveDraftIntoView();
                        }}
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                    >
                        {t("Navigate to Draft")}
                    </Button>
                ) : (
                    <>
                        {draftLocationLink && (
                            <LinkAsButton to={draftLocationLink} buttonType={ButtonTypes.TEXT_PRIMARY}>
                                {t("Navigate to Draft")}
                            </LinkAsButton>
                        )}
                    </>
                )}
            </>
        ),
    };

    const createOrUpdateDraftToast = () => {
        if (!draftLocationLink) {
            logDebug("Failed to create draft location link", { draftInThread });
        }

        draftToastID
            ? updateToast(draftToastID, draftToastContent, true)
            : setDraftToastID(addToast(draftToastContent));
    };

    useEffect(() => {
        // either we force to show the draft toast for storybook purposes
        // or we show the draft toast if there is a draft and the reply form is not visible
        if (props.forceShowDraftToast || (!visibleReplyFormRef?.current && isDraftInThread)) {
            createOrUpdateDraftToast();
            // we posted the draft, remove the toast
        } else if (!isDraftInThread && draftToastID) {
            removeToast(draftToastID);
            setDraftToastID(null);
        }
    }, [draftInThread]);

    useEffect(() => {
        const intersectionObserver = new IntersectionObserver(
            (entries: IntersectionObserverEntry[]) => {
                // hide the draft toast if there is one open
                if (entries[0].isIntersecting) {
                    draftToastID && updateToast(draftToastID, draftToastContent, false);
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

    useEffect(() => {
        return () => {
            toastIDToRemove.current && removeToast(toastIDToRemove.current);
        };
    }, []);

    return <></>;
}
