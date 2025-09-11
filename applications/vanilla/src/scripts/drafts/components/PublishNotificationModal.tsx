/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useState } from "react";

import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { FramedModal } from "@library/modal/FramedModal";
import InputBlock from "@library/forms/InputBlock";
import ModalSizes from "@library/modal/ModalSizes";
import RadioButton from "@library/forms/RadioButton";
import { RadioGroupContext } from "@library/forms/RadioGroupContext";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { t } from "@vanilla/i18n";

const NotificationOptions = {
    notify: "notify",
    silent: "silent",
} as const;

export type NotificationOption = (typeof NotificationOptions)[keyof typeof NotificationOptions];

export default function PublishNotificationModal(props: {
    isVisible: boolean;
    onClose: () => void;
    onConfirm: (notify: NotificationOption) => Promise<void>;
    submitDisabled?: boolean;
}) {
    const { isVisible, onClose, onConfirm, submitDisabled } = props;

    const classFrameFooter = frameFooterClasses();
    const [notify, setNotify] = useState<NotificationOption>("notify");

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        e.stopPropagation();
        await onConfirm(notify);
    };

    if (!isVisible) {
        return null;
    }

    return (
        <FramedModal
            padding={"all"}
            title={t("Notification Options")}
            onClose={onClose}
            size={ModalSizes.MEDIUM}
            onFormSubmit={handleSubmit}
            footer={
                <>
                    <Button buttonType={ButtonTypes.TEXT} onClick={onClose} className={classFrameFooter.actionButton}>
                        {t("Cancel")}
                    </Button>
                    <Button
                        submit
                        disabled={submitDisabled}
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                        className={classFrameFooter.actionButton}
                    >
                        {submitDisabled ? <ButtonLoader /> : t("OK")}
                    </Button>
                </>
            }
        >
            <InputBlock legend={t("Do you want to notify followers about this post?")}>
                <RadioGroupContext.Provider
                    value={{
                        value: notify,
                        onChange: (value: NotificationOption) => setNotify(value),
                    }}
                >
                    <RadioButton label={t("Yes, send notifications")} value="notify" />
                    <RadioButton label={t("No, publish this post silently")} value="silent" />
                </RadioGroupContext.Provider>
            </InputBlock>
        </FramedModal>
    );
}
