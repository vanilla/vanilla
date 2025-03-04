/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommentDeleteMethod, IComment } from "@dashboard/@types/api/comment";
import { IServerError } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import InputBlock from "@library/forms/InputBlock";
import { RadioGroup } from "@library/forms/radioAsButtons/RadioGroup";
import { RadioButton } from "@library/forms/RadioButton";
import { RadioPicker } from "@library/forms/RadioPicker";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import { t } from "@library/utility/appUtils";
import { useMutation } from "@tanstack/react-query";
import { commentsBulkActions } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActions.classes";
import { CONTENT_REMOVED_STRING } from "@vanilla/addon-vanilla/comments/CommentItem";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { useState } from "react";

interface IDeleteCommentsFormProps {
    title?: string;
    description?: React.ReactNode;
    successMessage?: string;
    commentIDs: Array<IComment["commentID"]>;
    close: () => void;
    onMutateSuccess?: (deleteMethod?: CommentDeleteMethod) => Promise<void>;
}

export default function DeleteCommentsForm(props: IDeleteCommentsFormProps) {
    const { close, commentIDs, onMutateSuccess } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = commentsBulkActions();
    const { addToast } = useToast();

    const [topLevelErrors, setTopLevelErrors] = useState<IError[]>([]);
    const [deleteMethod, setDeleteMethod] = useState<CommentDeleteMethod>(CommentDeleteMethod.TOMBSTONE);

    const deleteComments = useMutation({
        mutationFn: async (params: CommentsApi.DeleteParams) => await CommentsApi.delete(params),
        mutationKey: ["delete_comments", commentIDs],
        onSuccess() {
            addToast({
                dismissible: true,
                body: <>{props.successMessage ?? t("Posts have been deleted.")}</>,
            });
            void onMutateSuccess?.(deleteMethod);
            close();
        },

        onError(error: IServerError) {
            if (error.description || error.message) {
                setTopLevelErrors([
                    {
                        message: error.description || error.message,
                    },
                ]);
            }
        },
    });

    return (
        <form
            onSubmit={async (e) => {
                e.preventDefault();
                e.stopPropagation();
                await deleteComments.mutateAsync({ commentIDs: commentIDs, deleteMethod });
            }}
        >
            <Frame
                header={<FrameHeader closeFrame={close} title={props.title ?? t("Delete Posts")} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <>
                                {topLevelErrors && topLevelErrors.length > 0 && (
                                    <Message
                                        type="error"
                                        stringContents={topLevelErrors[0].message}
                                        icon={<ErrorIcon />}
                                        contents={<ErrorMessages errors={topLevelErrors} />}
                                        className={classes.topLevelError}
                                    />
                                )}
                                <div className={classes.modalHeader}>
                                    {props.description ?? (
                                        <>
                                            <Translate
                                                source={"You are about to delete <0/> posts."}
                                                c0={commentIDs.length}
                                            />{" "}
                                            {t("These posts will remain in the change log.")}{" "}
                                            {t("Are you sure you want to continue?")}
                                        </>
                                    )}
                                </div>
                                <InputBlock required label={t("Delete Method")}>
                                    <RadioButton
                                        checked={deleteMethod === CommentDeleteMethod.TOMBSTONE}
                                        onChecked={() => setDeleteMethod(CommentDeleteMethod.TOMBSTONE)}
                                        label={t("Tombstone")}
                                        value={CommentDeleteMethod.TOMBSTONE}
                                        note={`${t(
                                            "Hide comment author information and replace comment content with",
                                        )} "${t(CONTENT_REMOVED_STRING)}"`}
                                    />
                                    <RadioButton
                                        checked={deleteMethod === CommentDeleteMethod.FULL}
                                        onChecked={() => setDeleteMethod(CommentDeleteMethod.FULL)}
                                        label={t("Full")}
                                        value={CommentDeleteMethod.FULL}
                                        note={t("Completely remove the comment and all associated child comments.")}
                                    />
                                </InputBlock>
                            </>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button buttonType={ButtonTypes.TEXT} onClick={close} className={classFrameFooter.actionButton}>
                            {t("Cancel")}
                        </Button>
                        <Button buttonType={ButtonTypes.TEXT_PRIMARY} className={classFrameFooter.actionButton} submit>
                            {deleteComments.isLoading ? <ButtonLoader /> : t("Delete")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
