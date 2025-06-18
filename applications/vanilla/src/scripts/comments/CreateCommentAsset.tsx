/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import { MyValue } from "@library/vanilla-editor/typescript";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import isEqual from "lodash-es/isEqual";
import { useEffect, useRef, useState } from "react";
import { CommentEditor, ICommentEditorRefHandle } from "@vanilla/addon-vanilla/comments/CommentEditor";
import { t } from "@vanilla/i18n";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { isCommentDraftMeta, makeCommentDraft, makeCommentDraftProps } from "@vanilla/addon-vanilla/drafts/utils";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import Translate from "@library/content/Translate";
import { useCreateCommentContext } from "@vanilla/addon-vanilla/posts/CreateCommentContext";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useDebouncedInput } from "@dashboard/hooks";
import ModalConfirm from "@library/modal/ModalConfirm";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { IError } from "@library/errorPages/CoreErrorMessages";
import ErrorMessages from "@library/forms/ErrorMessages";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import { commentEditorClasses } from "@vanilla/addon-vanilla/comments/CommentEditor.classes";
import { IFieldError } from "@library/json-schema-forms";
import { IApiError } from "@library/@types/api/core";

interface IProps {
    isPreview?: boolean;
    title?: string;
    description?: string;
    subtitle?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    renderInContainer?: boolean;
    onDiscard?: () => void;
    replyTo?: string;
}

/**
 * The widget instance for creating a comment.
 */
export function CreateCommentAsset(props: IProps) {
    const { closed } = useCommentThreadParentContext();
    const { createCommentLocation, setCreateCommentLocation } = useCreateCommentContext();

    const { draft } = useDraftContext();
    const { hasPermission } = usePermissionsContext();
    const canReply = hasPermission("comments.add");

    if (closed) {
        if (!canReply) {
            return null;
        }
    }

    const isTopLevelComment = draft ? !(draft?.attributes?.draftMeta ?? {}).hasOwnProperty("commentPath") : false;
    const isDraftCommentForOriginalPost = !!(draft && isTopLevelComment);

    const continueReply = () => {
        return (
            <>
                {t("You have a saved draft.")}{" "}
                <Button
                    onClick={() => {
                        setCreateCommentLocation("widget");
                    }}
                    buttonType={ButtonTypes.TEXT_PRIMARY}
                >
                    {t("Continue Replying")}
                </Button>
            </>
        );
    };

    const startNewReply = () => {
        return (
            <>
                {t("You have a saved reply for another comment.")}{" "}
                <Button
                    onClick={() => {
                        setCreateCommentLocation("widget");
                    }}
                    buttonType={ButtonTypes.TEXT_PRIMARY}
                >
                    {t("Create a new reply")}
                </Button>
            </>
        );
    };

    return (
        <>
            <span id={"create-comment"}>
                {/* When the open form is rendered in the widget */}
                {createCommentLocation === "widget" && (
                    <HomeWidgetContainer
                        title={props.title}
                        description={props.description}
                        subtitle={props.subtitle}
                        options={props.containerOptions}
                        depth={2}
                    >
                        {draft ? (
                            <>{isDraftCommentForOriginalPost ? <AddComment /> : startNewReply()}</>
                        ) : (
                            <AddComment />
                        )}
                    </HomeWidgetContainer>
                )}
                {/* When the open form is rendered in the original post asset */}
                {createCommentLocation === "original-post" && (
                    <>
                        {draft ? (
                            <>{isDraftCommentForOriginalPost ? continueReply() : startNewReply()}</>
                        ) : (
                            continueReply()
                        )}
                    </>
                )}
            </span>
        </>
    );
}

/**
 * The widget instance for creating a comment below the original post.
 */
export function CreateOriginalPostReply(props: IProps) {
    const { createCommentLocation, setCreateCommentLocation } = useCreateCommentContext();

    return (
        <>
            {createCommentLocation === "original-post" && (
                <div id={"create-comment"} style={{ marginTop: globalVariables().itemList.padding.top }}>
                    <AddComment
                        replyTo={props.replyTo}
                        onDiscard={() => {
                            setCreateCommentLocation("widget");
                        }}
                    />
                </div>
            )}
        </>
    );
}

/**
 * All the things needed to add a comment to a post.
 */
export function AddComment(props: IProps & { replyTo?: string }) {
    const commentParent = useCommentThreadParentContext();
    const { addToast } = useToast();
    const [value, setValue] = useState<MyValue | undefined>();
    const [editorKey, setEditorKey] = useState(0);
    const [error, setError] = useState<IError | null>(null);
    const [fieldError, setFieldError] = useState<IFieldError[] | null>(null);
    const classes = commentEditorClasses();

    const { draftID, draft, updateDraft, removeDraft, enableAutosave, disableAutosave } = useDraftContext();
    const { setVisibleReplyFormRef, draftToRemove, setDraftToRemove } = useCreateCommentContext();

    const queryClient = useQueryClient();
    const toastError = useToastErrorHandler();

    const resetState = () => {
        setValue(undefined);
        setInputCache(undefined);
        setEditorKey(new Date().getTime());
        setVisibleReplyFormRef && setVisibleReplyFormRef({ current: null });
        setError(null);
        setFieldError(null);
    };

    useEffect(() => {
        if (draftToRemove && draftToRemove?.attributes?.draftMeta) {
            if (isCommentDraftMeta(draftToRemove.attributes.draftMeta)) {
                // Validate if the draft is not a top level comment draft
                if (!draftToRemove.attributes.draftMeta.hasOwnProperty("commentPath")) {
                    resetState();
                    setDraftToRemove(null);
                }
            }
        }
    }, [draftToRemove]);

    const postMutation = useMutation({
        mutationFn: async (body: string) => {
            disableAutosave();
            setError(null);
            const response = await CommentsApi.post({
                format: "rich2",
                parentRecordType: commentParent.recordType,
                parentRecordID: commentParent.recordID,
                ...(draftID && { draftID }),
                body,
            });
            removeDraft(draftID ?? window.location.pathname, true);

            if ("status" in response && response.status === 202) {
                addToast({
                    body: t("Your comment will appear after it is approved."),
                    dismissible: true,
                    autoDismiss: false,
                });
            }
            await queryClient.invalidateQueries({ queryKey: ["discussion"] });
            await queryClient.invalidateQueries({ queryKey: ["commentList"] });
            await queryClient.invalidateQueries({ queryKey: ["commentThread"] });
            resetState();
            enableAutosave();
            return response;
        },
        onError: (error: IApiError) => {
            toastError(error);
            setError(error);
            setFieldError(error?.errors?.body ?? null);
        },
    });

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
        if (value && commentParent.recordID && !isEqual(value, EMPTY_RICH2_BODY)) {
            const draftPayload = makeCommentDraft({
                body: value,
                format: "rich2",
                parentRecordType: commentParent.recordType,
                parentRecordID: commentParent.recordID,
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
        if (draftID && draft && isTopLevelDraft && isEqual(value, EMPTY_RICH2_BODY)) {
            setDeleteDraftModal(true);
        }
    }, [debouncedValue]);

    const removeActiveDraft = () => {
        disableAutosave();

        const removed = removeDraft(draftID ?? window.location.pathname, true);

        if (removed) {
            resetState();
            props.onDiscard?.();
        }

        setDeleteDraftModal(false);
        enableAutosave();
    };

    // Create drafts props for the editor
    const draftProps = draftID && draft && isTopLevelDraft ? makeCommentDraftProps(draftID, draft) : {};

    const editorHandlerRef = useRef<ICommentEditorRefHandle>(null);

    useEffect(() => {
        if (editorHandlerRef.current?.formRef?.current && isTopLevelDraft) {
            setVisibleReplyFormRef && setVisibleReplyFormRef(editorHandlerRef.current.formRef);
        }
    }, [editorHandlerRef, isTopLevelDraft]);

    return (
        <>
            {error && fieldError && (
                <Message
                    className={classes.errorMessages}
                    type="error"
                    stringContents={error.message}
                    icon={<ErrorIcon />}
                    contents={<ErrorMessages errors={fieldError} />}
                />
            )}
            <CommentEditor
                ref={editorHandlerRef}
                title={
                    props.replyTo && (
                        <PageHeadingBox
                            depth={2}
                            title={<Translate source={"Replying to <0/>"} c0={props.replyTo} />}
                        />
                    )
                }
                editorKey={editorKey}
                value={value}
                onValueChange={setValue}
                format={"rich2"}
                onPublish={async (value) => {
                    await postMutation.mutateAsync(JSON.stringify(value));
                }}
                publishLoading={postMutation.isLoading}
                isPreview={props.isPreview}
                tertiaryActions={
                    <>
                        <Button
                            onClick={() => {
                                removeActiveDraft();
                            }}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            // Waiting for value to equal debounced value indicates the user stopped typing
                            // so we aren't trying to save a draft and discard it at the same time
                            disabled={isEqual(value, EMPTY_RICH2_BODY) || value !== debouncedValue}
                        >
                            {t("Discard Reply")}
                        </Button>
                    </>
                }
                {...draftProps}
            />
            <ModalConfirm
                title={
                    props.replyTo ? (
                        <Translate source={"Discard reply to <0/>"} c0={props.replyTo} />
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
                    removeActiveDraft();
                }}
                confirmTitle={t("Delete Draft")}
                cancelTitle={t("Restore")}
            >
                {t("You have an empty draft saved. Do you want to delete it?")}
            </ModalConfirm>
        </>
    );
}

export default CreateCommentAsset;
