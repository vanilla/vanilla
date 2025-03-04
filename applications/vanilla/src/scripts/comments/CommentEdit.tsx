/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment, ICommentEdit } from "@dashboard/@types/api/comment";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { MyValue } from "@library/vanilla-editor/typescript";
import { useMutation } from "@tanstack/react-query";
import { commentEditorClasses } from "@vanilla/addon-vanilla/comments/CommentEditor.classes";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { ICommentEditorRefHandle, CommentEditor } from "@vanilla/addon-vanilla/comments/CommentEditor";
import { t } from "@vanilla/i18n";
import { useLayoutEffect, useRef, useState } from "react";
import { logDebug } from "@vanilla/utils";
import { useToast } from "@library/features/toaster/ToastContext";
import { IError } from "@library/errorPages/CoreErrorMessages";

interface IProps {
    comment: IComment;
    commentEdit: ICommentEdit;
    onClose: () => void;
    onSuccess?: (data?: IComment) => Promise<void>;
}

export function CommentEdit(props: IProps) {
    const { comment, commentEdit } = props;
    const classes = commentEditorClasses();
    const [value, setValue] = useState<MyValue | undefined>();
    const initialFormat = commentEdit.format;
    const initialValue = initialFormat !== "rich2" ? comment.body : commentEdit.body;

    const { addToast } = useToast();

    const isComment = (apiResponse: Awaited<ReturnType<typeof CommentsApi.post>>): apiResponse is IComment => {
        return apiResponse.hasOwnProperty("commentID");
    };

    const patchMutation = useMutation({
        mutationFn: async (params: CommentsApi.PatchParams) => {
            const response = await CommentsApi.patch(comment.commentID, params).catch((error: IError) => {
                logDebug("Error updating comment", error);
                addToast({
                    body: <>{error.message ? error.message : t("Something went wrong updating this comment.")}</>,
                    dismissible: true,
                    autoDismiss: false,
                });
                return null;
            });
            if (response) {
                if (!isComment(response)) {
                    addToast({
                        body: t("Your comment will appear after it is approved."),
                        dismissible: true,
                        autoDismiss: false,
                    });
                    return response;
                }
                setValue(undefined);
                !!props.onSuccess && (await props.onSuccess?.(response));
            }
            return response;
        },
    });

    const editorRef = useRef<ICommentEditorRefHandle | null>(null);

    useLayoutEffect(() => {
        if (editorRef.current) {
            editorRef.current.focusCommentEditor();
        }
    }, []);

    const handleSubmit = async () => {
        await patchMutation.mutateAsync({
            body: JSON.stringify(value),
            format: "rich2",
        });
    };

    return (
        <>
            <CommentEditor
                className={classes.editorSpacing}
                ref={editorRef}
                title={<></>}
                editorKey={2}
                format={initialFormat}
                initialValue={initialValue}
                value={value}
                onValueChange={setValue}
                postLabel={t("Save")}
                onPublish={async () => {
                    await handleSubmit();
                }}
                publishLoading={patchMutation.isLoading}
                tertiaryActions={
                    <>
                        <Button
                            onClick={() => {
                                props.onClose?.();
                            }}
                            disabled={patchMutation.isLoading}
                            buttonType={ButtonTypes.STANDARD}
                        >
                            {t("Cancel")}
                        </Button>
                    </>
                }
                autoFocus
            />
        </>
    );
}
