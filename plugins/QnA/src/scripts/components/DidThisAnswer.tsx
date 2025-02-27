/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@vanilla/i18n";
import { IComment, QnAStatus } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUserID } from "@library/features/users/userHooks";
import DidThisAnswerClasses from "@QnA/components/DidThisAnswer.classes";
import { usePatchAnswerStatus } from "@QnA/hooks/usePatchAnswerStatus";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";

interface IProps {
    comment: IComment;
    onMutateSuccess?: () => Promise<void>;
}

export default function DidThisAnswer(props: IProps) {
    const { comment, onMutateSuccess } = props;
    const commentParent = useCommentThreadParentContext();

    const classes = DidThisAnswerClasses();

    const currentUserID = useCurrentUserID();
    const currentUserIsDiscussionAuthor = commentParent.insertUserID === currentUserID;
    const { hasPermission } = usePermissionsContext();
    const canChangeStatus = currentUserIsDiscussionAuthor || hasPermission("curation.manage");

    const status: QnAStatus | undefined = comment.attributes?.answer?.status;

    const patchAnswerStatus = usePatchAnswerStatus(comment);

    if (!comment.isTroll && canChangeStatus && status === QnAStatus.PENDING) {
        return (
            <div className={classes.root}>
                <span>{t("Did this answer the question?")}</span>

                <button
                    title={t("Accept this answer.")}
                    className={classes.button}
                    onClick={async function () {
                        await patchAnswerStatus.mutateAsync(QnAStatus.ACCEPTED);
                        !!onMutateSuccess && (await onMutateSuccess?.());
                    }}
                >
                    {t("Yes")}
                </button>
                <button
                    title={t("Reject this answer.")}
                    className={classes.button}
                    onClick={async function () {
                        await patchAnswerStatus.mutateAsync(QnAStatus.REJECTED);
                        !!onMutateSuccess && (await onMutateSuccess?.());
                    }}
                >
                    {t("No")}
                </button>
            </div>
        );
    }

    return null;
}
