/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { getMeta, t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import PostSettings from "@library/features/discussions/forms/PostSettings";
import { IPostSettingsProps } from "@library/features/discussions/forms/PostSettings.types";

interface IProps {
    /** This is used for legacy views compatibility */
    modalOnly?: boolean;
    /** This is used for legacy views compatibility */
    initialAction?: IPostSettingsProps["initialAction"];
    discussion: IDiscussion;
    onSuccess?: () => Promise<void>;
}

export default function DiscussionOptionsPostSettings(props: IProps) {
    const { discussion, onSuccess } = props;

    const [initialAction, setInitialAction] = useState<IPostSettingsProps["initialAction"]>(
        props.initialAction ?? null,
    );
    const close = () => setInitialAction(null);

    async function handleSuccess() {
        !!onSuccess && (await onSuccess());
        close();
    }

    return (
        <>
            {!props.modalOnly && (
                <>
                    <DropDownItemButton onClick={() => setInitialAction("move")}>{t("Move Post")}</DropDownItemButton>
                    <DropDownItemButton onClick={() => setInitialAction("change")}>
                        {t("Change Post Type")}
                    </DropDownItemButton>
                </>
            )}
            <Modal isVisible={!!initialAction} size={ModalSizes.LARGE}>
                <PostSettings
                    onClose={() => close()}
                    discussion={discussion}
                    initialAction={initialAction}
                    handleSuccess={handleSuccess}
                    isLegacyPage={props.modalOnly}
                />
            </Modal>
        </>
    );
}
