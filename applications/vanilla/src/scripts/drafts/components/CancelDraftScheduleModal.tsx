/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useState } from "react";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { DraftStatus, IDraft } from "@vanilla/addon-vanilla/drafts/types";
import ModalConfirm from "@library/modal/ModalConfirm";
import { IDraftModalProps } from "@vanilla/addon-vanilla/drafts/components/DraftScheduleModal";
import { useCancelDraftScheduleMutation } from "@vanilla/addon-vanilla/drafts/Draft.hooks";
import ErrorMessages from "@library/forms/ErrorMessages";
import { draftsClasses } from "@vanilla/addon-vanilla/drafts/Drafts.classes";

export function CancelDraftScheduleModal(
    props: Omit<IDraftModalProps, "notificationOption"> & {
        draftID: NonNullable<IDraft["draftID"]>;
    },
) {
    const { draftID, isVisible, onVisibilityChange, onSubmit } = props;
    const toast = useToast();

    const [error, setError] = useState<IError>();

    const cancelDraftScheduleMutation = useCancelDraftScheduleMutation();

    const handleMove = async (draftID: IDraft["draftID"]) => {
        try {
            onSubmit
                ? await onSubmit?.({ draftID, draftStatus: DraftStatus.DRAFT, dateScheduled: undefined })
                : await cancelDraftScheduleMutation.mutateAsync(draftID);
            toast.addToast({
                autoDismiss: true,
                body: <>{t("Success! Schedule has been cancelled.")}</>,
            });
            onVisibilityChange(false);
        } catch (error) {
            const fieldError = error.errors && Object.values(error.errors ?? {})[0];
            setError({
                message:
                    (fieldError ?? []).length && fieldError[0]["message"] ? fieldError[0]["message"] : error.message,
            });
        }
    };

    return (
        <ModalConfirm
            isVisible={isVisible}
            title={t("Cancel Draft Schedule")}
            onCancel={() => onVisibilityChange(false)}
            onConfirm={() => handleMove(draftID)}
        >
            {error && <ErrorMessages errors={[error]} className={draftsClasses().verticalGap} />}
            {t("Are you sure you want to cancel the schedule?")}
        </ModalConfirm>
    );
}
