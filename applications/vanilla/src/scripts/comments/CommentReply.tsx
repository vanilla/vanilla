/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
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
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { logDebug } from "@vanilla/utils";
import { t } from "@vanilla/i18n";
import { forwardRef, ReactNode, useEffect, useState } from "react";
import { IThreadItem } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { isNestedReply } from "@vanilla/addon-vanilla/comments/NestedCommentUtils";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { ICommentEditorRefHandle, CommentEditor } from "@vanilla/addon-vanilla/comments/CommentEditor";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import isEqual from "lodash-es/isEqual";
import { isCommentDraftMeta, makeCommentDraft, makeCommentDraftProps } from "@vanilla/addon-vanilla/drafts/utils";
import { useDebouncedInput } from "@dashboard/hooks";
import ModalConfirm from "@library/modal/ModalConfirm";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import ErrorMessages from "@library/forms/ErrorMessages";
import { commentEditorClasses } from "@vanilla/addon-vanilla/comments/CommentEditor.classes";
import { IFieldError } from "@library/json-schema-forms";
import { MentionsProvider } from "@library/features/users/suggestion/MentionsContext";

interface IProps {
    threadItem: (IThreadItem & { type: "comment" }) | (IThreadItem & { type: "reply" });
    className?: string;
    editorContainerClasses?: string;
    title?: ReactNode;
    onSuccess?: () => void;
    onCancel?: () => void;
    skipReplyThreadItem?: boolean;
}

export const DRAFT_CONTENT_KEY = "commentDraft";
export const DRAFT_PARENT_ID_AND_PATH_KEY = "commentDraftParentIDAndPath";

export const CommentReply = forwardRef(function ThreadCommentEditor(
    props: IProps,
    ref: React.Ref<ICommentEditorRefHandle>,
) {
    const [editorKey, setEditorKey] = useState(new Date().getTime());
    const [value, setValue] = useState<MyValue | undefined>();
    const [error, setError] = useState<IError | null>(null);
    const [fieldError, setFieldError] = useState<IFieldError[] | null>(null);
    const commentParent = useCommentThreadParentContext();
    const threadParent = useCommentThreadParentContext();
    const { draftID, draft, updateDraft, removeDraft, enableAutosave, disableAutosave } = useDraftContext();
    const { addReplyToThread, removeReplyFromThread, constructReplyFromComment } = useNestedCommentContext();
    const threadItem = isNestedReply(props.threadItem) ? props.threadItem : constructReplyFromComment(props.threadItem);
    const classes = commentEditorClasses();

    const commentMeta =
        draft?.attributes?.draftMeta &&
        isCommentDraftMeta(draft?.attributes?.draftMeta) &&
        draft?.attributes?.draftMeta;

    const isTopLevelDraft = commentMeta && !commentMeta.hasOwnProperty("commentPath");

    const [inputCache, setInputCache] = useState<MyValue | undefined>(value);
    const [deleteDraftModal, setDeleteDraftModal] = useState(false);
    const debouncedValue = useDebouncedInput(value, 1200);

    useEffect(() => {
        // Update draft
        if (value && threadItem.parentCommentID && !isEqual(value, EMPTY_RICH2_BODY)) {
            const draftPayload = makeCommentDraft({
                body: value,
                format: "rich2",
                parentRecordType: threadParent.recordType,
                parentRecordID: threadParent.recordID,
                commentPath: threadItem.path,
                commentParentID: threadItem.parentCommentID,
            });
            draftPayload && updateDraft(draftPayload);
        }
        // Cache the longest value to restore it if the user deletes it all in error
        if (JSON.stringify(value ?? {}).length >= JSON.stringify(inputCache ?? {}).length) {
            setInputCache(value);
        }
    }, [value]);

    useEffect(() => {
        // Delete draft is the value is empty
        if (draftID && draft && !isTopLevelDraft && isEqual(value, EMPTY_RICH2_BODY)) {
            setDeleteDraftModal(true);
        }
    }, [debouncedValue]);

    const { addToast } = useToast();

    const isComment = (apiResponse: Awaited<ReturnType<typeof CommentsApi.post>>): apiResponse is IComment => {
        return apiResponse.hasOwnProperty("commentID");
    };

    // Used to post the comment to the server
    const postMutation = useMutation({
        mutationKey: ["postComment", threadParent.recordType, threadParent.recordID, threadItem?.parentCommentID],
        mutationFn: async (richContent: MyValue) => {
            setError(null);
            setFieldError(null);
            const body = safelySerializeJSON(richContent);
            if (body) {
                const response = await CommentsApi.post({
                    format: "rich2",
                    parentRecordType: threadParent.recordType,
                    parentRecordID: threadParent.recordID,
                    ...(draftID && { draftID }),
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
                const commentWithExpands = await CommentsApi.get(response.commentID, {
                    expand: ["reactions"],
                    quoteParent: false,
                });
                addReplyToThread(threadItem, commentWithExpands, !!props.skipReplyThreadItem);
                props.onSuccess?.();
                return commentWithExpands;
            }
        },
    });

    // Create drafts props for the editor
    const draftProps = draftID && draft ? makeCommentDraftProps(draftID, draft) ?? {} : {};

    const discardReply = () => {
        removeDraft();
        props.onCancel?.();
        removeReplyFromThread(threadItem);
        setDeleteDraftModal(false);
    };

    return (
        <MentionsProvider recordID={commentParent.recordID} recordType={commentParent.recordType}>
            <CommentEditor
                ref={ref}
                title={
                    <>
                        {props.title ?? (
                            <PageHeadingBox
                                title={<Translate source={"Replying to <0/>"} c0={threadItem.replyingTo} />}
                            />
                        )}
                        {error && fieldError && (
                            <Message
                                className={classes.errorMessages}
                                type="error"
                                stringContents={error.message}
                                icon={<ErrorIcon />}
                                contents={<ErrorMessages errors={fieldError} />}
                            />
                        )}
                    </>
                }
                className={props.className}
                editorKey={editorKey}
                format={"rich2"}
                value={value}
                onValueChange={(value) => {
                    setValue(value);
                }}
                onPublish={async (value) => {
                    disableAutosave();
                    try {
                        await postMutation.mutateAsync(value);
                        removeDraft(true);
                    } catch (error) {
                        logDebug("Error posting comment", error);
                        setError(error);
                        setFieldError(error?.errors?.body ?? null);
                        addToast({
                            body: (
                                <>{error.message ? error.message : t("Something went wrong posting your comment.")}</>
                            ),
                            dismissible: true,
                            autoDismiss: false,
                        });
                    }
                    enableAutosave();
                }}
                publishLoading={postMutation.isLoading}
                tertiaryActions={
                    <>
                        <Button
                            onClick={() => {
                                discardReply();
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
            <ModalConfirm
                title={
                    threadItem.replyingTo ? (
                        <Translate source={"Discard reply to <0/>"} c0={threadItem.replyingTo} />
                    ) : (
                        t("Discard Reply")
                    )
                }
                isVisible={deleteDraftModal}
                onCancel={() => {
                    // This needs to be delayed to allow the editor to propagate the restored value
                    setTimeout(() => {
                        setValue(inputCache);
                        setEditorKey(new Date().getTime());
                        setDeleteDraftModal(false);
                    }, 100);
                }}
                onConfirm={() => {
                    discardReply();
                }}
                confirmTitle={t("Delete Draft")}
                cancelTitle={t("Restore")}
            >
                {t("You have an empty draft saved. Do you want to delete it?")}
            </ModalConfirm>
        </MentionsProvider>
    );
});
