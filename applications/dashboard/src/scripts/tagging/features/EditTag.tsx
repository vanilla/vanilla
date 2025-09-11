/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import TagForm from "@dashboard/tagging/components/TagForm";
import { usePatchTag } from "@dashboard/tagging/TaggingSettings.context";
import { ITagItem, TagFormValues } from "@dashboard/tagging/taggingSettings.types";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useState } from "react";

export default function EditTag(props: { tag: ITagItem; onSuccess?: () => Promise<void>; scopeEnabled?: boolean }) {
    const { addToast } = useToast();
    const { tag, onSuccess, scopeEnabled = false } = props;

    const [modalOpen, setModalOpen] = useState(false);
    const { mutateAsync: patchTag } = usePatchTag(tag.tagID);

    async function handleSuccess() {
        addToast({
            autoDismiss: true,
            body: t("Tag updated successfully"),
        });
        await onSuccess?.();
        setModalOpen(false);
    }

    async function handleSubmit(values: TagFormValues) {
        const response = await patchTag(values);
        await handleSuccess();
        return response;
    }

    const title = t("Edit Tag");

    const initialValues = {
        name: tag.name,
        urlcode: tag.urlcode,
        ...(scopeEnabled && {
            scope: tag.scope,
            scopeType: tag.scopeType,
        }),
    };

    return (
        <>
            <Button
                ariaLabel={t("Edit Tag")}
                buttonType={ButtonTypes.ICON_COMPACT}
                onClick={() => {
                    setModalOpen(true);
                }}
            >
                <Icon icon="edit" />
            </Button>

            <Modal isVisible={modalOpen} size={ModalSizes.LARGE} exitHandler={() => setModalOpen(false)}>
                <TagForm
                    title={title}
                    initialValues={initialValues}
                    onSubmit={handleSubmit}
                    onClose={() => setModalOpen(false)}
                    scopeEnabled={scopeEnabled}
                />
            </Modal>
        </>
    );
}
