/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import TagForm from "@dashboard/tagging/components/TagForm";
import { usePostTag } from "@dashboard/tagging/TaggingSettings.context";
import { TagFormValues } from "@dashboard/tagging/taggingSettings.types";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { useState } from "react";

export default function AddTag(props: { disabled?: boolean; onSuccess?: () => Promise<void>; scopeEnabled?: boolean }) {
    const { addToast } = useToast();
    const { onSuccess, disabled = false, scopeEnabled = false } = props;
    const [modalOpen, setModalOpen] = useState(false);
    const { mutateAsync: postTag } = usePostTag();

    async function handleSuccess() {
        addToast({
            autoDismiss: true,
            body: t("Tag added successfully"),
        });

        await onSuccess?.();
        setModalOpen(false);
    }

    async function handleSubmit(values: TagFormValues) {
        const response = await postTag(values);
        await handleSuccess();
        return response;
    }

    const title = t("Add Tag");

    return (
        <>
            <Button
                disabled={disabled}
                buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                onClick={() => {
                    setModalOpen(true);
                }}
            >
                {title}
            </Button>

            <Modal isVisible={modalOpen} size={ModalSizes.LARGE} exitHandler={() => setModalOpen(false)}>
                <TagForm
                    title={title}
                    onSubmit={handleSubmit}
                    onClose={() => setModalOpen(false)}
                    scopeEnabled={scopeEnabled}
                />
            </Modal>
        </>
    );
}
