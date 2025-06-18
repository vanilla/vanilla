/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import CreateCommentAsset from "@vanilla/addon-vanilla/comments/CreateCommentAsset";

export function CreateCommentAssetPreview(props: React.ComponentProps<typeof CreateCommentAsset>) {
    return (
        <LayoutWidget>
            <CreateCommentAsset {...props} isPreview={true} />
        </LayoutWidget>
    );
}
