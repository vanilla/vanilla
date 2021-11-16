/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { BulkActionsModal, BulkActionTypes } from "@library/features/discussions/BulkActionsModal";
import React from "react";

interface IProps {
    action: BulkActionTypes | null;
    setAction(action: BulkActionTypes | null): void;
}

export function BulkActionsManager(props: IProps) {
    const { action, setAction } = props;
    return (
        <BulkActionsModal
            visibility={!!action}
            onVisibilityChange={(visibility) => {
                if (visibility === false) {
                    setAction(null);
                }
            }}
            bulkActionType={action}
        />
    );
}
