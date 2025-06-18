/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { t } from "@vanilla/i18n";
import { useState } from "react";
import { useToast } from "@library/features/toaster/ToastContext";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { DraftScheduleModal } from "@vanilla/addon-vanilla/drafts/components/DraftScheduleModal";
import { DraftStatus, IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { CancelDraftScheduleModal } from "@vanilla/addon-vanilla/drafts/components/CancelDraftScheduleModal";
import { DeleteDraftModal } from "@vanilla/addon-vanilla/drafts/components/DeleteDraftModal";

interface IProps {
    draft: IDraft;
    isSchedule: boolean;
}

export function DraftListItemOptionsMenu(props: IProps) {
    const { isSchedule, draft } = props;
    const { permaLink, editUrl } = draft;
    const [isScheduleModalVisible, setIsScheduleModalVisible] = useState(false);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [isMoveModalVisible, setIsMoveModalVisible] = useState(false);

    const toast = useToast();

    const isErroredSchedule = (draft as IDraft).draftStatus === DraftStatus.ERROR;

    return (
        <DropDown name={t("Draft Item Options")} flyoutType={FlyoutType.LIST} asReachPopover>
            {isSchedule && !isErroredSchedule && (
                <>
                    <DropDownItemButton
                        onClick={async () => {
                            await navigator.clipboard.writeText(permaLink ?? "");
                            toast.addToast({
                                body: <>{t("Post permalink copied to clipboard.")}</>,
                                autoDismiss: true,
                            });
                        }}
                    >
                        {t("Copy Permalink")}
                    </DropDownItemButton>
                    <DropDownItemSeparator />
                </>
            )}
            <DropDownItemLink to={editUrl}>{t("Edit Post")}</DropDownItemLink>
            {isSchedule && !isErroredSchedule && (
                <>
                    <DropDownItemButton
                        onClick={() => {
                            setIsScheduleModalVisible(true);
                        }}
                    >
                        {t("Edit Schedule")}
                    </DropDownItemButton>
                    <DropDownItemButton
                        onClick={() => {
                            setIsMoveModalVisible(true);
                        }}
                    >
                        {t("Cancel Schedule")}
                    </DropDownItemButton>
                </>
            )}
            <DropDownItemSeparator />
            <DropDownItemButton
                onClick={() => {
                    setIsDeleteModalVisible(true);
                }}
            >
                {t("Delete")}
            </DropDownItemButton>
            <DraftScheduleModal
                draftID={draft.draftID}
                recordType={draft.recordType}
                isVisible={isScheduleModalVisible}
                onVisibilityChange={setIsScheduleModalVisible}
                initialSchedule={draft.dateScheduled}
                isScheduledDraft={true}
            />
            <CancelDraftScheduleModal
                draftID={draft.draftID}
                recordType={draft.recordType}
                isVisible={isMoveModalVisible}
                onVisibilityChange={setIsMoveModalVisible}
            />
            <DeleteDraftModal
                draftID={draft.draftID}
                recordType={draft.recordType}
                isVisible={isDeleteModalVisible}
                onVisibilityChange={setIsDeleteModalVisible}
                isSchedule={isSchedule}
            />
        </DropDown>
    );
}
