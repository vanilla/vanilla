/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { BulkActionsToast } from "@library/bulkActions/BulkActionsToast";
import { useDiscussionByIDs } from "@library/features/discussions/discussionHooks";
import { RecordID } from "@vanilla/utils";
import { useMemo } from "react";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { PermissionMode } from "@library/features/users/Permission";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { BulkAction } from "@library/bulkActions/BulkActions.types";
import { t } from "@vanilla/i18n";
import Translate from "@library/content/Translate";

interface IProps {
    /** The number of selected discussions */
    selectedIDs: number[] | RecordID[];
    /** Function to clear all selected discussions */
    handleSelectionClear(): void;
    /** Function to delete selected discussions */
    handleBulkDelete(): void;
    /** Function to move selected discussions */
    handleBulkMove(): void;
    /** Function to merge selected discussions */
    handleBulkMerge(): void;
    /** Function to close selected discussions */
    handleBulkClose(): void;
}

/**
 * This is the toast notification which is displayed when multiple discussions are selected
 */
export function DiscussionBulkActionsToast(props: IProps) {
    const { selectedIDs, handleBulkClose, handleBulkDelete, handleBulkMerge, handleBulkMove, handleSelectionClear } =
        props;
    const { hasPermission } = usePermissionsContext();

    const sanitizedIDs = useMemo(() => {
        return selectedIDs.map((id: RecordID) => Number(id));
    }, [selectedIDs]);

    const discussions = useDiscussionByIDs(sanitizedIDs ?? []);

    /**
     * Check one permission against a list of discussions or comments
     */
    const checkPermissions = (permission: string, records: Record<RecordID, IDiscussion> | null) => {
        if (records) {
            return Object.values(records)
                .map((record) => {
                    if (
                        !hasPermission(permission, {
                            resourceType: "category",
                            mode: PermissionMode.RESOURCE_IF_JUNCTION,
                            resourceID: record.categoryID,
                        })
                    ) {
                        return record.name;
                    }
                    return null;
                })
                .filter((entry) => entry);
        }
        return [];
    };

    // If all of the selected discussions are already closed, disable the close button
    const isAllClosed = useMemo<boolean>(() => {
        if (discussions) {
            const notClosed = Object.values(discussions).filter(({ closed }) => !closed);
            return notClosed.length === 0;
        }
        return false;
    }, [discussions]);

    // Create a list of records which we do not have permission to operate on.
    // If the list is empty, we have the required permissions.
    const uneditableRecords = useMemo(() => {
        return checkPermissions("discussions.manage", discussions);
    }, [discussions]);

    return (
        <BulkActionsToast
            selectionMessage={<Translate source={"You have selected <0/> discussions."} c0={sanitizedIDs.length} />}
            handleSelectionClear={handleSelectionClear}
            bulkActionsButtons={[
                {
                    action: BulkAction.MOVE,
                    notAllowed: uneditableRecords.length > 0,
                    handler: handleBulkMove,
                    notAllowedMessage: t("You don’t have the edit permission on the following discussions:"),
                },
                {
                    action: BulkAction.MERGE,
                    notAllowed: uneditableRecords.length > 0 || sanitizedIDs.length < 2,
                    handler: handleBulkMerge,
                    notAllowedMessage:
                        sanitizedIDs.length < 2
                            ? t("You must select at least 2 discussions to merge.")
                            : `${t(
                                  "You don’t have the edit permission on the following discussions:",
                              )} ${uneditableRecords.join(", ")}`,
                },
                {
                    action: BulkAction.CLOSE,
                    notAllowed: uneditableRecords.length > 0 || isAllClosed,
                    handler: handleBulkClose,
                    notAllowedMessage: isAllClosed
                        ? t("Selected discussions are already closed.")
                        : `${t(
                              "You don't have the close permission on the following discussions:",
                          )} ${uneditableRecords.join(", ")}`,
                },
                {
                    action: BulkAction.DELETE,
                    notAllowed: uneditableRecords.length > 0,
                    handler: handleBulkDelete,
                    notAllowedMessage: `${t(
                        "You don’t have the delete permission on the following discussions:",
                    )} ${uneditableRecords.join(", ")}`,
                },
            ]}
        />
    );
}
