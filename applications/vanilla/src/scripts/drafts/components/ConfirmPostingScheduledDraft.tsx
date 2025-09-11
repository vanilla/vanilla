/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import DateTime, { DateFormats } from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@vanilla/i18n";

export default function ConfirmPostingScheduledDraft(props: {
    isVisible: boolean;
    onClose: () => void;
    onConfirm: () => Promise<void>;
    scheduledDate?: string;
    isLoading?: boolean;
}) {
    const { isVisible, onClose, onConfirm, isLoading, scheduledDate } = props;

    return (
        <ModalConfirm
            isVisible={isVisible}
            onCancel={onClose}
            title={t("Override Schedule")}
            onConfirm={async () => {
                await onConfirm();
                onClose();
            }}
            cancelTitle={t("Cancel")}
            confirmTitle={t("Post Now")}
            isConfirmLoading={isLoading}
        >
            {scheduledDate && (
                <Translate
                    source={
                        "This content is currently scheduled to be published on <0/>. Are you sure you want to post it immediately instead?"
                    }
                    c0={<DateTime date={scheduledDate} type={DateFormats.EXTENDED} />}
                />
            )}
        </ModalConfirm>
    );
}
