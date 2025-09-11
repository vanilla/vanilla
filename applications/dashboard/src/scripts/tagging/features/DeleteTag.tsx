/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { ITagItem } from "@dashboard/tagging/taggingSettings.types";
import { useDeleteTag } from "@dashboard/tagging/TaggingSettings.context";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ModalConfirm from "@library/modal/ModalConfirm";
import { Icon } from "@vanilla/icons";
import { t } from "@vanilla/i18n";
import Translate from "@library/content/Translate";

interface IProps {
    tag: ITagItem;
    onSuccess?: () => Promise<void>;
}

export default function DeleteTag(props: IProps) {
    const { tag, onSuccess } = props;
    const [confirmModalOpen, setConfirmModalOpen] = useState(false);

    const toast = useToast();
    const toastError = useToastErrorHandler();

    const { mutateAsync: deleteTag, isLoading } = useDeleteTag(tag.tagID);

    function closeConfirmModal() {
        setConfirmModalOpen(false);
    }

    async function handleDeleteTag() {
        try {
            await deleteTag();
            closeConfirmModal();
            toast.addToast({
                autoDismiss: true,
                body: <Translate source="You have successfully deleted tag: <0/>" c0={tag.name} />,
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
                onClick={() => setConfirmModalOpen(true)}
                ariaLabel={t("Delete Tag")}
            >
                <Icon icon="delete" />
            </Button>

            <ModalConfirm
                isVisible={confirmModalOpen}
                title={t("Delete Tag")}
                onCancel={closeConfirmModal}
                onConfirm={handleDeleteTag}
                confirmTitle={t("Delete")}
                isConfirmLoading={isLoading}
            >
                <Translate source="Do you wish to delete tag: <0/>" c0={tag.name} />
            </ModalConfirm>
        </>
    );
}
