/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { BulkActionsToast } from "@library/bulkActions/BulkActionsToast";
import { RecordID } from "@vanilla/utils";
import Translate from "@library/content/Translate";
import { BulkAction, IAdditionalBulkAction } from "@library/bulkActions/BulkActions.types";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { PermissionMode } from "@library/features/users/Permission";
import { t } from "@vanilla/i18n";

interface IProps {
    /** The number of selected comments */
    selectedIDs: number[] | RecordID[];
    /** The category ID of the selected comments discussion*/
    categoryID: number;
    /** Function to clear all selected comments */
    handleSelectionClear(): void;
    /** Function to delete selected comments */
    handleBulkDelete(): void;
    /** Function to split selected comments */
    handleBulkSplit(): void;
    /** Additional bulk actions */
    additionalBulkActions?: IAdditionalBulkAction[];
    /** Set the bulk action, same function as known handlers above, but we might have additional bulk actions */
    setAction(action: BulkAction | null): void;
}

/**
 * This is the toast notification which is displayed when multiple discussions are selected
 */
export function CommentsBulkActionsToast(props: IProps) {
    const {
        selectedIDs,
        categoryID,
        handleBulkSplit,
        handleBulkDelete,
        handleSelectionClear,
        additionalBulkActions,
        setAction,
    } = props;
    const { hasPermission } = usePermissionsContext();

    const hasSplitPermission =
        hasPermission("discussions.add", {
            resourceType: "category",
            resourceID: categoryID,
            mode: PermissionMode.RESOURCE_IF_JUNCTION,
        }) || hasPermission("curation.manage");

    // map additional bulk actions to bulk action buttons
    const additionalBulkActionButtons = additionalBulkActions?.map((bulkAction) => ({
        action: bulkAction.action,
        notAllowed: !hasPermission(bulkAction.permission),
        handler: () => setAction?.(bulkAction.action),
        notAllowedMessage: t(bulkAction.notAllowedMessage), // doing the translation here instead of top level components
    }));

    return (
        <BulkActionsToast
            handleSelectionClear={handleSelectionClear}
            selectionMessage={<Translate source={"You have selected <0/> comments."} c0={selectedIDs.length} />}
            bulkActionsButtons={[
                {
                    action: BulkAction.SPLIT,
                    notAllowed: !hasSplitPermission,
                    handler: handleBulkSplit,
                    notAllowedMessage: t("You don't have required permission to split selected comments."),
                },
                {
                    action: BulkAction.DELETE,
                    notAllowed: !hasPermission("comments.delete"),
                    handler: handleBulkDelete,
                    notAllowedMessage: t("You don't have required permission to delete selected comments."),
                },
                ...(additionalBulkActionButtons || []),
            ]}
        />
    );
}
