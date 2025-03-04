/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import ConvertPostForm from "@library/features/discussions/forms/ConvertPostForm";

interface IProps {
    discussion: IDiscussion;
    onSuccess?: () => Promise<void>;
}

export default function DiscussionOptionsConvert(props: IProps) {
    const { discussion, onSuccess } = props;

    const [isModalVisible, setIsModalVisible] = useState(false);
    const open = () => setIsModalVisible(true);
    const close = () => setIsModalVisible(false);

    async function handleSuccess() {
        !!onSuccess && (await onSuccess());
        close();
    }

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Change Post Type")}</DropDownItemButton>
            <Modal isVisible={isModalVisible} exitHandler={close} size={ModalSizes.MEDIUM}>
                <ConvertPostForm onClose={close} discussion={discussion} onSuccess={handleSuccess} />
            </Modal>
        </>
    );
}
