/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDraft } from "@dashboard/@types/api/draft";
import { useDebouncedInput } from "@dashboard/hooks";
import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { safelySerializeJSON } from "@library/utility/appUtils";
import { MyValue } from "@library/vanilla-editor/typescript";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { useMutation } from "@tanstack/react-query";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import {
    IDraftProps,
    DraftIndicatorPosition,
    NewCommentEditor,
    ICommentEditorRefHandle,
} from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { DraftsApi } from "@vanilla/addon-vanilla/thread/DraftsApi";
import { logDebug } from "@vanilla/utils";
import { t } from "@vanilla/i18n";
import { useLocalStorage } from "@vanilla/react-utils";
import { forwardRef, MutableRefObject, ReactNode, useEffect, useMemo, useRef, useState } from "react";
import { IThreadItem, CommentDraftParentIDAndPath } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { isThreadReply } from "@vanilla/addon-vanilla/thread/threadUtils";

interface IProps {
    threadItem: (IThreadItem & { type: "comment" }) | (IThreadItem & { type: "reply" });
    className?: string;
    editorContainerClasses?: string;
    title?: ReactNode;
    onSuccess?: () => void;
    onCancel?: () => void;
    autoFocus?: boolean;
    skipReplyThreadItem?: boolean;
    draftPosition?: DraftIndicatorPosition;
}

export const DRAFT_CONTENT_KEY = "commentDraft";
export const DRAFT_PARENT_ID_AND_PATH_KEY = "commentDraftParentIDAndPath";

export const ThreadCommentEditor = forwardRef(function ThreadCommentEditor(
    props: IProps,
    ref: React.Ref<ICommentEditorRefHandle>,
) {
    const [value, setValue] = useState<MyValue | undefined>();
    const { discussion, addReplyToThread, removeReplyFromThread, draft, constructReplyFromComment } =
        useCommentThread();
    const threadItem = isThreadReply(props.threadItem) ? props.threadItem : constructReplyFromComment(props.threadItem);

    // Keeps what the user has typed in local storage
    const [cacheDraftContent, setCacheDraftContent] = useLocalStorage(
        `${DRAFT_CONTENT_KEY}-${discussion.discussionID}`,
        draft?.body ?? "",
    );

    // Keeps reference to which comment is being replied to in local storage and full path to that comment
    const [cacheDraftParentIDAndPath, setCacheDraftParentIDAndPath] = useLocalStorage<CommentDraftParentIDAndPath>(
        `${DRAFT_PARENT_ID_AND_PATH_KEY}-${discussion.discussionID}`,
        {
            parentCommentID: props.threadItem.parentCommentID,
            path: props.threadItem.path,
        },
    );

    // Store of the draft on the server, if any
    const serverDraft = useRef<IDraftProps | undefined>(draft);

    const clearDraftCache = () => {
        serverDraft.current = undefined;
        setCacheDraftContent(JSON.stringify(EMPTY_RICH2_BODY));
        setCacheDraftParentIDAndPath(null);
    };

    const { addToast, updateToast } = useToast();

    const isComment = (apiResponse: Awaited<ReturnType<typeof CommentsApi.post>>): apiResponse is IComment => {
        return apiResponse.hasOwnProperty("commentID");
    };

    // Ensure the cacheDraftParentIDAndPath is updated when the parentCommentID changes
    useEffect(() => {
        setCacheDraftParentIDAndPath({
            parentCommentID: props.threadItem.parentCommentID,
            path: props.threadItem.path,
        });
    }, [props, setCacheDraftParentIDAndPath]);

    // Used to post the comment to the server
    const postMutation = useMutation({
        mutationKey: ["postComment", discussion.discussionID, threadItem?.parentCommentID],
        mutationFn: async (richContent: MyValue) => {
            const body = safelySerializeJSON(richContent);
            if (body) {
                const response = await CommentsApi.post({
                    format: "rich2",
                    discussionID: discussion.discussionID,
                    ...(serverDraft.current?.draftID && { draftID: serverDraft.current?.draftID }),
                    body,
                    parentCommentID: `${threadItem?.parentCommentID}`,
                });
                if (!isComment(response)) {
                    addToast({
                        body: t("Your comment will appear after it is approved."),
                        dismissible: true,
                        autoDismiss: false,
                    });
                    return response;
                }
                const commentWithExpands = await CommentsApi.get(response.commentID, { expand: ["reactions"] });
                return commentWithExpands;
            }
        },
        onSuccess(data) {
            clearDraftCache();
            addReplyToThread(threadItem, data as IComment, !!props.skipReplyThreadItem);
            props.onSuccess?.();
        },
        onError(error: IError) {
            logDebug("Error posting comment", error);
            addToast({
                body: <>{error.message ? error.message : t("Something went wrong posting your comment.")}</>,
                dismissible: true,
                autoDismiss: false,
            });
        },
    });

    // We are caching drafts in local storage and then sending them to the server after a delay
    // Keeps track of the last time the draft was saved to local storage
    const lastSaved = useRef<Date | null>(null);
    const isCachedDraftEmpty = cacheDraftContent === JSON.stringify(EMPTY_RICH2_BODY);

    // Persist the draft to local storage and update the last saved time
    useEffect(() => {
        const serializedDraft = value ? safelySerializeJSON(value) : undefined;
        if (serializedDraft) {
            setCacheDraftContent(serializedDraft);
            lastSaved.current = new Date();
        }
    }, [setCacheDraftContent, value]);

    // Debounce the draft so we don't hit the api too often
    const debouncedDraft = useDebouncedInput(cacheDraftContent, 500);

    const errorToastID = useRef<string | null>(null);

    // Post or patch draft
    const draftMutation = useMutation({
        mutationFn: async () => {
            const payload: DraftsApi.PostParams = {
                attributes: {
                    format: "rich2",
                    body: debouncedDraft,
                },
                recordType: "comment",
                parentRecordID: discussion.discussionID,
            };

            let result;

            if (serverDraft.current?.draftID) {
                result = DraftsApi.patch({
                    ...payload,
                    draftID: serverDraft.current?.draftID,
                });
            } else {
                result = DraftsApi.post(payload);
            }

            if (!result || result instanceof Error) {
                throw result;
            }

            return result;
        },
        onSuccess(data: IDraft) {
            lastSaved.current = new Date();
            serverDraft.current = {
                draftID: data.draftID,
                body: data.attributes.body,
                dateUpdated: data.dateUpdated,
                format: data.attributes.format,
            };
        },
        onError(error: IError) {
            if (!errorToastID.current) {
                errorToastID.current = addToast({
                    body: <>{error.message}</>,
                    autoDismiss: true,
                });
            } else {
                updateToast(errorToastID.current, {
                    body: <>{error.message}</>,
                    autoDismiss: true,
                });
            }
        },
        mutationKey: ["draft", discussion.discussionID],
    });

    // Make API call to save draft
    useEffect(() => {
        // Ensure new value is not an empty draft
        if (debouncedDraft && debouncedDraft !== JSON.stringify(EMPTY_RICH2_BODY)) {
            // Ensure the current value is different from the last saved draft
            if (draft?.body !== debouncedDraft && !postMutation.isLoading) {
                draftMutation.mutateAsync();
            }
        }
    }, [debouncedDraft]);

    // Create drafts props for the editor
    const draftProps = useMemo(() => {
        if (cacheDraftParentIDAndPath?.parentCommentID === threadItem.parentCommentID) {
            const draftContent = cacheDraftContent ?? serverDraft.current?.body;
            const draft: IDraftProps = {
                draftID: serverDraft.current?.draftID ?? `${DRAFT_CONTENT_KEY}-${discussion.discussionID}`,
                body: draftContent,
                dateUpdated: lastSaved.current?.toISOString() ?? "",
                format: "rich",
            };
            return {
                draft: draft,
                draftLastSaved: !isCachedDraftEmpty ? lastSaved.current : null,
                manualDraftSave: false,
                draftIndicatorPosition: props.draftPosition ?? DraftIndicatorPosition.WITHIN,
            };
        }
        return {};
    }, [cacheDraftContent, cacheDraftParentIDAndPath, props.draftPosition]);

    return (
        <NewCommentEditor
            autoFocus={props.autoFocus}
            ref={ref}
            title={
                props.title ?? (
                    <PageHeadingBox title={<Translate source={"Replying to <0/>"} c0={threadItem.replyingTo} />} />
                )
            }
            className={props.className}
            editorKey={1}
            value={value}
            onValueChange={(value) => {
                setValue(value);
            }}
            onPublish={async (value) => {
                postMutation.mutateAsync(value);
            }}
            publishLoading={postMutation.isLoading}
            tertiaryActions={
                <>
                    <Button
                        onClick={() => {
                            clearDraftCache();
                            props.onCancel?.() ?? removeReplyFromThread(threadItem);
                        }}
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                    >
                        {t("Discard Reply")}
                    </Button>
                </>
            }
            postLabel={t("Post Comment Reply")}
            containerClasses={props.editorContainerClasses}
            {...draftProps}
        />
    );
});
