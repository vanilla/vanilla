import React from "react";
import { IInterest } from "@dashboard/interestsSettings/Interests.types";
import { Icon } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Translate from "@library/content/Translate";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@vanilla/i18n";
import { useDeleteInterest } from "@dashboard/interestsSettings/InterestsSettings.hooks";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";

export function DeleteInterest(props: { interest: IInterest; onSuccess?: () => Promise<void> }) {
    const toast = useToast();
    const toastError = useToastErrorHandler();

    const { interest, onSuccess } = props;
    const [confirmModalOpen, setConfirmModalOpen] = React.useState(false);

    const { mutateAsync: deleteInterest } = useDeleteInterest(interest.interestID);

    function closeConfirmModal() {
        setConfirmModalOpen(false);
    }

    async function handleDeleteInterest() {
        try {
            await deleteInterest();
            closeConfirmModal();
            toast.addToast({
                autoDismiss: true,
                body: <Translate source="You have successfully deleted interest: <0/>" c0={interest.name} />,
            });
            await onSuccess?.();
        } catch (err) {
            toastError(err);
        }
    }

    return (
        <>
            <Button
                buttonType={ButtonTypes.ICON_COMPACT}
                onClick={() => {
                    setConfirmModalOpen(true);
                }}
            >
                <Icon icon="delete" />
            </Button>

            <ModalConfirm
                isVisible={confirmModalOpen}
                title={t("Delete Interest")}
                onCancel={() => {
                    closeConfirmModal();
                }}
                onConfirm={handleDeleteInterest}
            >
                <Translate source="Do you wish to delete interest: <0/>" c0={interest.name} />
            </ModalConfirm>
        </>
    );
}
