/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useToastErrorHandler } from "@library/features/toaster/ToastContext";
import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { SuggestedAnswerContent } from "@library/suggestedAnswers/SuggestedAnswerItem";
import { suggestedAnswersClasses } from "@library/suggestedAnswers/SuggestedAnswers.classes";
import { useAcceptSuggestion } from "@library/suggestedAnswers/SuggestedAnswers.hooks";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { t } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";

export interface IAcceptedAnswerProps {
    suggestion: ISuggestedAnswer;
    discussion: IDiscussion;
    commentID: IComment["commentID"];
    className?: string;
    onMutateSuccess?: () => Promise<void>;
}

export function AcceptedAnswerComment(props: IAcceptedAnswerProps) {
    const { suggestion, className, discussion, onMutateSuccess, commentID } = props;
    const classes = suggestedAnswersClasses();
    const acceptAnswer = useAcceptSuggestion(discussion.discussionID);
    const toastError = useToastErrorHandler();
    const currentUser = useCurrentUser();
    const { hasPermission } = usePermissionsContext();
    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: discussion.categoryID,
    };
    const canUndo =
        hasPermission("comments.delete", permissionOptions) || currentUser?.userID === discussion.insertUserID;

    const handleUndoAcceptAnswer = async () => {
        try {
            await acceptAnswer({
                suggestion: suggestion.aiSuggestionID,
                accept: false,
                commentID: suggestion.commentID,
            });
            onMutateSuccess?.();
        } catch (err) {
            toastError(err);
        }
    };

    return (
        <>
            <SuggestedAnswerContent {...suggestion} className={className} />
            {canUndo && (
                <Button
                    buttonType={ButtonTypes.TEXT_PRIMARY}
                    className={classes.answerButton}
                    onClick={handleUndoAcceptAnswer}
                >
                    <Icon icon="data-undo" size="compact" />
                    {t("Undo Accept Answer")}
                </Button>
            )}
        </>
    );
}
