/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { BulkActionsModal } from "@library/bulkActions/BulkActionsModal";
import { BulkAction, IAdditionalBulkAction } from "@library/bulkActions/BulkActions.types";

interface IProps {
    action: BulkAction | null;
    setAction(action: BulkAction | null): void;
    recordType: "discussion" | "comment";
    additionalBulkActions?: IAdditionalBulkAction[];
}

export function BulkActionsManager(props: IProps) {
    const { action, setAction, recordType, additionalBulkActions } = props;
    return (
        <BulkActionsModal
            visibility={!!action}
            onVisibilityChange={(visibility) => {
                if (visibility === false) {
                    setAction(null);
                }
            }}
            bulkActionType={action}
            recordType={recordType}
            additionalBulkActions={additionalBulkActions}
        />
    );
}
