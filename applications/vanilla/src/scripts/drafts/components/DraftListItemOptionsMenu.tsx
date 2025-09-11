/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DraftStatus, IDraft } from "@vanilla/addon-vanilla/drafts/types";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";

import { CancelDraftScheduleModal } from "@vanilla/addon-vanilla/drafts/components/CancelDraftScheduleModal";
import { DeleteDraftModal } from "@vanilla/addon-vanilla/drafts/components/DeleteDraftModal";
import { DraftScheduleModal } from "@vanilla/addon-vanilla/drafts/components/DraftScheduleModal";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { isPostDraftMeta } from "@vanilla/addon-vanilla/drafts/utils";
import { t } from "@vanilla/i18n";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useState } from "react";
import { useToast } from "@library/features/toaster/ToastContext";

interface IProps {
    draft: IDraft;
    isSchedule: boolean;
}

const BASE_PERMISSIONS_OPTIONS: IPermissionOptions = {
    resourceType: "category",
    mode: PermissionMode.RESOURCE_IF_JUNCTION,
};

export function DraftListItemOptionsMenu(props: IProps) {
    const { isSchedule, draft } = props;
    const { permaLink, editUrl } = draft;
    const [isScheduleModalVisible, setIsScheduleModalVisible] = useState(false);
    const [isDeleteModalVisible, setIsDeleteModalVisible] = useState(false);
    const [isMoveModalVisible, setIsMoveModalVisible] = useState(false);

    const toast = useToast();
    const { hasPermission } = usePermissionsContext();

    const isErroredSchedule = (draft as IDraft).draftStatus === DraftStatus.ERROR;

    // AIDEV-NOTE: Using type guard to safely access draftMeta properties
    const draftMeta = draft.attributes.draftMeta;
    const isPostDraft = draftMeta && isPostDraftMeta(draftMeta);

    // AIDEV-NOTE: Determine categoryID for permission check - similar to CreatePostFormAsset.tsx
    const categoryID = isPostDraft ? draftMeta.categoryID : null;

    const canPostSilently = hasPermission(
        "silentPosting.allow",
        categoryID
            ? {
                  ...BASE_PERMISSIONS_OPTIONS,
                  resourceID: +categoryID,
              }
            : undefined,
    );

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
                notificationOption={isPostDraft ? draftMeta.publishedSilently ?? null : null}
                canPostSilently={canPostSilently}
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
