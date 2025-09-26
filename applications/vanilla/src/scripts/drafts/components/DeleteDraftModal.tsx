/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useState } from "react";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import Translate from "@library/content/Translate";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import ModalConfirm from "@library/modal/ModalConfirm";
import { IDraftModalProps } from "@vanilla/addon-vanilla/drafts/components/DraftScheduleModal";
import { useDraftDeleteMutation } from "@vanilla/addon-vanilla/drafts/Draft.hooks";
import ErrorMessages from "@library/forms/ErrorMessages";
import { draftsClasses } from "@vanilla/addon-vanilla/drafts/Drafts.classes";

interface IProps extends IDraftModalProps {
    draftID: NonNullable<IDraft["draftID"]>;
    isSchedule: boolean;
}

export function DeleteDraftModal(props: IProps) {
    const { draftID, isVisible, onVisibilityChange, recordType, isSchedule } = props;
    const toast = useToast();

    const [error, setError] = useState<IError>();

    const deleteDraftMutation = useDraftDeleteMutation();

    const handleDelete = async (draftID: IDraft["draftID"]) => {
        try {
            await deleteDraftMutation.mutateAsync(draftID);
            toast.addToast({
                autoDismiss: true,
                body: (
                    <>
                        <Translate
                            source={"Success! <0/> <1/> deleted."}
                            c0={isSchedule ? "Scheduled" : "Draft"}
                            c1={recordType}
                        />
                    </>
                ),
            });
            onVisibilityChange(false);
        } catch (error) {
            setError(error);
        }
    };

    return (
        <ModalConfirm
            isVisible={isVisible}
            title={t("Delete")}
            onCancel={() => {
                onVisibilityChange(false);
                setError(undefined);
            }}
            onConfirm={() => handleDelete(draftID)}
        >
            {error && <ErrorMessages errors={[error]} className={draftsClasses().verticalGap} />}
            <Translate
                source={"Are you sure you want to delete <0/> <1/>?"}
                c0={isSchedule ? "scheduled" : "draft"}
                c1={recordType}
            />
        </ModalConfirm>
    );
}
